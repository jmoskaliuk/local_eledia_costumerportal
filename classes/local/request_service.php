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
 * Request service — creates and retrieves customer requests.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\local;

/**
 * Handles customer request creation (local DB + async sync to Directus)
 * and retrieval from Directus.
 */
class request_service {
    /** @var api_client */
    private api_client $client;

    /** @var installation_service */
    private installation_service $installationsvc;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->client          = new api_client();
        $this->installationsvc = new installation_service();
    }

    /**
     * Valid request types.
     */
    public const REQUEST_TYPES = [
        'plugin_request',
        'feature_request',
        'storage_request',
        'consulting_request',
    ];

    /**
     * Create a new request. Saves locally first; sync task will push to Directus.
     *
     * @param string      $requesttype  One of self::REQUEST_TYPES.
     * @param string      $message      Customer message (min 20 chars enforced by form).
     * @param string|null $catalogentryid Optional UUID of the related catalog entry.
     * @return int Local DB record ID.
     * @throws \moodle_exception If installation not configured or invalid type.
     */
    public function create(string $requesttype, string $message, ?string $catalogentryid = null): int {
        global $DB, $USER;

        if (!in_array($requesttype, self::REQUEST_TYPES, true)) {
            throw new \moodle_exception('error_request_failed', 'local_customerportal');
        }

        $installationid = $this->installationsvc->get_installation_id();

        $record = (object) [
            'installation_id'  => $installationid,
            'catalog_entry_id' => $catalogentryid ?? null,
            'request_type'     => $requesttype,
            'message'          => $message,
            'userid'           => $USER->id,
            'status'           => 'pending',
            'directus_id'      => null,
            'timecreated'      => time(),
            'timemodified'     => time(),
        ];

        return $DB->insert_record('local_customerportal_request', $record);
    }

    /**
     * Sync a single pending local request to Directus.
     * Called by the scheduled task.
     *
     * @param \stdClass $record Local DB record.
     * @return bool True on success.
     */
    public function sync_to_directus(\stdClass $record): bool {
        global $DB;

        $payload = [
            'installation_id'  => $record->installation_id,
            'catalog_entry_id' => $record->catalog_entry_id ?: null,
            'request_type'     => $record->request_type,
            'message'          => $record->message,
        ];

        try {
            $result     = $this->client->post('/v1/portal/requests', $payload);
            $directusid = $result['data']['id'] ?? null;

            $DB->update_record('local_customerportal_request', (object) [
                'id'           => $record->id,
                'status'       => 'synced',
                'directus_id'  => $directusid,
                'timemodified' => time(),
            ]);

            return true;
        } catch (\moodle_exception $e) {
            $DB->update_record('local_customerportal_request', (object) [
                'id'           => $record->id,
                'status'       => 'error',
                'timemodified' => time(),
            ]);

            return false;
        }
    }

    /**
     * List requests for the current installation from Directus.
     * Falls back to local DB records if Directus is unreachable.
     *
     * @return array List of request records.
     */
    public function list_for_installation(): array {
        global $DB;

        try {
            $installationid = $this->installationsvc->get_installation_id();
            $result = $this->client->get('/v1/portal/requests', [
                'installation_id' => $installationid,
            ]);
            return $result['data'] ?? [];
        } catch (\moodle_exception $e) {
            // Fallback: show locally stored records.
            $records = $DB->get_records(
                'local_customerportal_request',
                ['installation_id' => get_config('local_customerportal', 'installation_id')],
                'timecreated DESC'
            );
            return array_values($records);
        }
    }
}
