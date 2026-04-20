<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Installation snapshot and plugin sync service.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\local;

/**
 * Writes the installation digital twin and plugin inventory to Directus.
 */
class sync_service {
    /** @var api_client */
    private api_client $client;

    /** @var installation_service */
    private installation_service $installationsvc;

    /** @var int */
    private int $retrydelayseconds;

    /** @var health_service|null */
    private ?health_service $health;

    /**
     * Constructor.
     *
     * @param api_client|null $client
     * @param installation_service|null $installationsvc
     * @param int $retrydelayseconds
     * @param health_service|null $health
     */
    public function __construct(
        ?api_client $client = null,
        ?installation_service $installationsvc = null,
        int $retrydelayseconds = 5,
        ?health_service $health = null
    ) {
        $this->client = $client ?? new api_client();
        $this->installationsvc = $installationsvc ?? new installation_service($this->client);
        $this->retrydelayseconds = max(0, $retrydelayseconds);
        $this->health = $health;
    }

    /**
     * Build the base installation payload shared by registration and snapshot sync.
     *
     * @return array
     */
    private function build_installation_payload_base(): array {
        global $CFG, $DB, $SITE;

        $moodleversion = $this->normalize_moodle_version((string) ($CFG->release ?? ''));

        $siteidentifier = trim((string) get_config('local_customerportal', 'site_label'));
        if ($siteidentifier === '') {
            $siteidentifier = trim((string) ($SITE->fullname ?? ''));
        }
        if ($siteidentifier === '') {
            $siteidentifier = trim((string) format_string(\get_site()->fullname));
        }

        $now = time();
        $thirtydaysago = $now - (30 * DAYSECS);

        $usercountactive = (int) $DB->count_records_select('user', 'deleted = 0 AND lastaccess > ?', [$thirtydaysago]);
        $usercounttotal = (int) $DB->count_records('user', ['deleted' => 0]);

        $health = $this->health ?? new health_service();

        $payload = [
            'label' => $siteidentifier,
            'flavour' => (string) get_config('local_customerportal', 'flavour') ?: 'lms',
            'moodle_version' => $moodleversion,
            'release_channel' => (string) get_config('local_customerportal', 'release_channel') ?: 'lts',
            'profile' => (string) get_config('local_customerportal', 'profile') ?: 'managed',
            'sla_level' => (string) get_config('local_customerportal', 'sla_level') ?: 'level_1',
            'user_tier' => (string) get_config('local_customerportal', 'user_tier') ?: '250',
            'addon_bbb_enabled' => !empty(get_config('local_customerportal', 'addon_bbb_enabled')),
            'addon_solr_enabled' => !empty(get_config('local_customerportal', 'addon_solr_enabled')),
            'user_count_active_30d' => $usercountactive,
            'user_count_total' => $usercounttotal,
            'health_overall' => $health->get_overall_status(),
        ];

        $quota = get_config('local_customerportal', 'storage_quota_gb');
        if ($quota !== false && $quota !== '' && is_numeric($quota) && (float) $quota > 0) {
            $payload['storage_quota_gb'] = (float) $quota;
        }

        return $payload;
    }

    /**
     * Build the payload for POST /v1/portal/installations/snapshot.
     *
     * `profile`, `site_label` and `storage_quota_gb` come from plugin settings.
     * `health_overall` is derived via health_service from local cron + task signals
     * (never hardcoded; see docs/05-quality.md → bug10).
     *
     * @return array
     */
    public function build_snapshot_payload(): array {
        $payload = $this->build_installation_payload_base();
        $payload['id'] = $this->installationsvc->get_installation_id();

        return $payload;
    }

    /**
     * Build the payload for POST /v1/portal/installations.
     *
     * This path is fallback-only. The primary registration path is shop / provisioning.
     *
     * @return array
     */
    public function build_registration_payload(): array {
        $payload = $this->build_installation_payload_base();
        unset(
            $payload['user_count_active_30d'],
            $payload['user_count_total'],
            $payload['storage_used_gb'],
            $payload['storage_quota_gb'],
            $payload['health_overall']
        );

        $existingid = $this->installationsvc->get_optional_installation_id();
        if ($existingid === '') {
            unset($payload['id']);
        } else {
            $payload['id'] = $existingid;
        }

        return $payload;
    }

    /**
     * Register or update this installation in Directus from the admin UI.
     *
     * @return array{success: bool, message: string, level: string, id: ?string, mode: ?string, status: ?int}
     */
    public function register_installation(): array {
        $missing = $this->get_missing_registration_config();
        if (!empty($missing)) {
            return [
                'success' => false,
                'message' => get_string(
                    'registration_error_missing_config',
                    'local_customerportal',
                    implode(', ', $missing)
                ),
                'level' => 'error',
                'id' => null,
                'mode' => null,
                'status' => 400,
            ];
        }

        $existingid = $this->installationsvc->get_optional_installation_id();
        $result = $this->post_with_retry_result(
            '/v1/portal/installations',
            $this->build_registration_payload()
        );

        if ($result['success']) {
            $returnedid = $this->extract_registration_id($result['response']);
            if (!$this->is_valid_uuid($returnedid)) {
                return [
                    'success' => false,
                    'message' => get_string('registration_error_invalid_response', 'local_customerportal'),
                    'level' => 'error',
                    'id' => null,
                    'mode' => null,
                    'status' => null,
                ];
            }

            $mode = $existingid === '' ? 'created' : 'updated';
            $this->store_registration_state($returnedid);

            return [
                'success' => true,
                'message' => get_string(
                    $mode === 'created' ? 'registration_success_created' : 'registration_success_updated',
                    'local_customerportal'
                ),
                'level' => 'success',
                'id' => $returnedid,
                'mode' => $mode,
                'status' => null,
            ];
        }

        if ($result['status'] === 409 && $existingid !== '') {
            $this->store_registration_state($existingid);
            return [
                'success' => true,
                'message' => get_string('registration_success_updated', 'local_customerportal'),
                'level' => 'warning',
                'id' => $existingid,
                'mode' => 'updated',
                'status' => 409,
            ];
        }

        return [
            'success' => false,
            'message' => $this->build_registration_error_message($result['status']),
            'level' => 'error',
            'id' => $existingid !== '' ? $existingid : null,
            'mode' => null,
            'status' => $result['status'],
        ];
    }

    /**
     * Normalize the Moodle release string to major.minor.
     *
     * @param string $release
     * @return string
     */
    public function normalize_moodle_version(string $release): string {
        $trimmed = trim($release);
        if (preg_match('/(\d+\.\d+)/', $trimmed, $matches)) {
            return $matches[1];
        }
        return $trimmed;
    }

    /**
     * Build the payload for POST /v1/portal/installations/{id}/plugins/sync.
     *
     * @return array{plugins: array}
     */
    public function build_plugins_sync_payload(): array {
        $plugins = $this->collect_current_plugins();
        $previousstate = $this->get_previous_plugin_state();

        foreach ($previousstate as $frankenstyle => $plugin) {
            if (isset($plugins[$frankenstyle])) {
                continue;
            }

            $plugins[$frankenstyle] = [
                'frankenstyle' => $frankenstyle,
                'installed_version' => (string) ($plugin['installed_version'] ?? ''),
                'status' => 'removed',
                'time_installed' => $plugin['time_installed'] ?? null,
            ];
        }

        ksort($plugins);

        return ['plugins' => array_values($plugins)];
    }

    /**
     * Sync the installation snapshot to Directus.
     *
     * @return bool
     */
    public function sync_snapshot(): bool {
        if (!$this->ensure_installation_registered()) {
            return false;
        }

        $payload = $this->build_snapshot_payload();
        $ok = $this->post_with_retry('/v1/portal/installations/snapshot', $payload, 'snapshot sync');
        if ($ok) {
            // Portal caches the installation row for 5 min; invalidate so the
            // next request reflects the freshly written digital twin.
            $this->installationsvc->invalidate_caches();
        }
        return $ok;
    }

    /**
     * Sync the plugin inventory to Directus and emit change events.
     *
     * @return bool
     */
    public function sync_plugins(): bool {
        if (!$this->ensure_installation_registered()) {
            return false;
        }

        $installationid = $this->installationsvc->get_installation_id();
        $currentplugins = $this->collect_current_plugins();
        $payload = $this->build_plugins_sync_payload();
        $path = '/v1/portal/installations/' . urlencode($installationid) . '/plugins/sync';

        if (!$this->post_with_retry($path, $payload, 'plugin sync')) {
            return false;
        }

        $this->emit_plugin_change_events($installationid, $currentplugins);
        $this->store_plugin_state($currentplugins);
        return true;
    }

    /**
     * Ensure the installation exists in Directus before runtime sync writes begin.
     *
     * The primary path remains shop-led provisioning. The plugin only falls back to
     * self-registration when no local success marker exists yet.
     *
     * @return bool
     */
    private function ensure_installation_registered(): bool {
        if (!empty(get_config('local_customerportal', 'installation_registered'))) {
            return true;
        }

        $result = $this->register_installation();

        if ($result['success']) {
            return true;
        }

        mtrace('installation registration failed: ' . $result['message']);
        return false;
    }

    /**
     * Collect all currently installed plugins from Moodle.
     *
     * @return array<string,array>
     */
    private function collect_current_plugins(): array {
        global $DB;

        $pluginman = \core_plugin_manager::instance();
        $plugintypes = $pluginman->get_plugins();
        $installtimes = $this->load_plugin_install_times();
        $plugins = [];

        foreach ($plugintypes as $typeplugins) {
            foreach ($typeplugins as $plugininfo) {
                if (empty($plugininfo->rootdir)) {
                    continue;
                }

                $frankenstyle = $this->normalize_frankenstyle((string) $plugininfo->component);
                if ($frankenstyle === '') {
                    continue;
                }

                $version = $plugininfo->versiondb ?? $plugininfo->versiondisk ?? '';
                $plugins[$frankenstyle] = [
                    'frankenstyle' => $frankenstyle,
                    'installed_version' => (string) $version,
                    'status' => 'installed',
                    'time_installed' => $installtimes[$frankenstyle] ?? null,
                ];
            }
        }

        return $plugins;
    }

    /**
     * Normalize a frankenstyle component name.
     *
     * @param string $frankenstyle
     * @return string
     */
    public function normalize_frankenstyle(string $frankenstyle): string {
        return \core_text::strtolower(trim($frankenstyle));
    }

    /**
     * Load the first known install timestamp per plugin from upgrade_log.
     *
     * @return array<string,string>
     */
    private function load_plugin_install_times(): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('upgrade_log')) {
            return [];
        }

        $sql = "SELECT plugin, MIN(timemodified) AS firstseen
                  FROM {upgrade_log}
                 WHERE plugin <> :core
                   AND plugin IS NOT NULL
              GROUP BY plugin";
        $rows = $DB->get_records_sql($sql, ['core' => 'core']);

        $times = [];
        foreach ($rows as $row) {
            $frankenstyle = $this->normalize_frankenstyle((string) $row->plugin);
            if ($frankenstyle === '' || empty($row->firstseen)) {
                continue;
            }
            $times[$frankenstyle] = gmdate('c', (int) $row->firstseen);
        }

        return $times;
    }

    /**
     * Post to Directus with two retries and retry-friendly logging.
     *
     * @param string $path
     * @param array $payload
     * @param string $label
     * @return bool
     */
    private function post_with_retry(string $path, array $payload, string $label): bool {
        $result = $this->post_with_retry_result($path, $payload);
        if ($result['success']) {
            return true;
        }

        mtrace($label . ' failed: ' . $result['message']);
        return false;
    }

    /**
     * Post to Directus with retries and expose the last error details to callers.
     *
     * @param string $path
     * @param array $payload
     * @return array{success: bool, message: string, status: ?int, response: ?array}
     */
    private function post_with_retry_result(string $path, array $payload): array {
        $lastmessage = '';
        $laststatus = null;
        $lastresponse = null;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $response = $this->client->post($path, $payload);
                return [
                    'success' => true,
                    'message' => '',
                    'status' => null,
                    'response' => $response,
                ];
            } catch (\moodle_exception $e) {
                $lastmessage = $e->getMessage();
                $laststatus = $this->extract_http_status($e);
                if ($attempt < 2) {
                    $this->wait_for_retry();
                }
            }
        }

        return [
            'success' => false,
            'message' => $lastmessage,
            'status' => $laststatus,
            'response' => $lastresponse,
        ];
    }

    /**
     * Extract the installation ID from the registration response.
     *
     * @param array|null $response
     * @return string
     */
    private function extract_registration_id(?array $response): string {
        if (!is_array($response)) {
            return '';
        }

        $data = $response['data'] ?? null;
        if (!is_array($data)) {
            return '';
        }

        return trim((string) ($data['id'] ?? ''));
    }

    /**
     * Validate whether the given value is a UUID string.
     *
     * @param string $value
     * @return bool
     */
    private function is_valid_uuid(string $value): bool {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value
        );
    }

    /**
     * Store successful registration markers locally.
     *
     * @param string $installationid
     * @return void
     */
    private function store_registration_state(string $installationid): void {
        set_config('installation_id', $installationid, 'local_customerportal');
        set_config('installation_registered', 1, 'local_customerportal');
        set_config('last_registration_at', time(), 'local_customerportal');
        $this->installationsvc->set_installation_id($installationid);
        $this->installationsvc->invalidate_caches();
    }

    /**
     * Return the names of required config values that are still missing.
     *
     * @return string[]
     */
    private function get_missing_registration_config(): array {
        $missing = [];
        if (trim((string) get_config('local_customerportal', 'directus_url')) === '') {
            $missing[] = get_string('settings_directus_url', 'local_customerportal');
        }
        if (trim((string) get_config('local_customerportal', 'directus_token')) === '') {
            $missing[] = get_string('settings_directus_token', 'local_customerportal');
        }

        return $missing;
    }

    /**
     * Map backend failure classes to admin-friendly messages.
     *
     * @param int|null $status
     * @return string
     */
    private function build_registration_error_message(?int $status): string {
        if ($status === 400) {
            return get_string('registration_error_bad_request', 'local_customerportal');
        }

        if ($status === 401 || $status === 403) {
            return get_string('registration_error_unauthorized', 'local_customerportal');
        }

        if ($status === 409) {
            return get_string('registration_error_conflict_missing_id', 'local_customerportal');
        }

        return get_string('registration_error_network', 'local_customerportal');
    }

    /**
     * Sleep between retries.
     */
    private function wait_for_retry(): void {
        if ($this->retrydelayseconds > 0) {
            sleep($this->retrydelayseconds);
        }
    }

    /**
     * Extract an HTTP status code from api_client exception text.
     *
     * @param \Throwable $exception
     * @return int|null
     */
    private function extract_http_status(\Throwable $exception): ?int {
        if (preg_match('/HTTP\s+(\d{3})\b/', $exception->getMessage(), $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Emit plugin lifecycle events for changes since the last successful sync.
     *
     * @param string $installationid
     * @param array<string,array> $currentplugins
     */
    private function emit_plugin_change_events(string $installationid, array $currentplugins): void {
        $previous = $this->get_previous_plugin_state();
        if (empty($previous)) {
            return;
        }

        $now = time();

        foreach ($currentplugins as $frankenstyle => $plugin) {
            if (!isset($previous[$frankenstyle])) {
                $version = (string) $plugin['installed_version'];
                $this->send_plugin_event($installationid, $frankenstyle, 'plugin_installed', $version, $now);
                continue;
            }

            $previousversion = (string) ($previous[$frankenstyle]['installed_version'] ?? '');
            $currentversion = (string) ($plugin['installed_version'] ?? '');
            if ($previousversion !== $currentversion) {
                $this->send_plugin_event($installationid, $frankenstyle, 'plugin_updated', $currentversion, $now);
            }
        }

        foreach ($previous as $frankenstyle => $plugin) {
            if (isset($currentplugins[$frankenstyle])) {
                continue;
            }

            $this->send_plugin_event(
                $installationid,
                $frankenstyle,
                'plugin_removed',
                (string) ($plugin['installed_version'] ?? ''),
                $now
            );
        }
    }

    /**
     * Send a single plugin lifecycle event to Directus.
     *
     * @param string $installationid
     * @param string $frankenstyle
     * @param string $eventtype
     * @param string $version
     * @param int $timestamp
     */
    private function send_plugin_event(
        string $installationid,
        string $frankenstyle,
        string $eventtype,
        string $version,
        int $timestamp
    ): void {
        global $USER;

        $payload = [
            'event_id' => $frankenstyle . '_' . $eventtype . '_' . $version . '_' . $timestamp,
            'event_type' => $eventtype,
            'payload' => json_encode([
                'frankenstyle' => $frankenstyle,
                'version' => $version,
            ]),
            'actor_type' => 'moodle_admin',
            'actor_id' => isset($USER->id) ? (string) $USER->id : '0',
            'happened_at' => gmdate('c', $timestamp),
        ];

        $path = '/v1/portal/installations/' . urlencode($installationid) . '/events';
        $this->post_with_retry($path, $payload, 'event sync');
    }

    /**
     * Read the last successful plugin state from plugin config.
     *
     * @return array<string,array>
     */
    private function get_previous_plugin_state(): array {
        $raw = (string) get_config('local_customerportal', 'last_plugin_sync_state');
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Persist the current plugin state after a successful sync.
     *
     * @param array<string,array> $plugins
     */
    private function store_plugin_state(array $plugins): void {
        set_config('last_plugin_sync_state', json_encode($plugins), 'local_customerportal');
    }
}
