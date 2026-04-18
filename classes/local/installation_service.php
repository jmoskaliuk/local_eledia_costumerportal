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
 * Installation service — fetches installation and overlay data from Directus.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\local;

/**
 * Provides installation data and catalog overlay context.
 * All calls go to the Directus private API (Bearer auth, server-side only).
 */
class installation_service {
    /** @var api_client */
    private api_client $client;

    /** @var string Configured installation UUID. */
    private string $installationid;

    /**
     * Constructor — reads installation ID from plugin config.
     */
    public function __construct() {
        $this->client         = new api_client();
        $this->installationid = (string) get_config('local_customerportal', 'installation_id');
    }

    /**
     * Returns the configured installation ID or throws if not set.
     *
     * @return string
     * @throws \moodle_exception
     */
    public function get_installation_id(): string {
        if (empty($this->installationid)) {
            throw new \moodle_exception(
                'error_installation_not_configured',
                'local_customerportal'
            );
        }
        return $this->installationid;
    }

    /**
     * Fetch installation stammdaten from Directus.
     *
     * @return array Keys: id, label, moodle_version, profile, snapshot_status, contact_email.
     * @throws \moodle_exception
     */
    public function get_installation(): array {
        $id       = $this->get_installation_id();
        $cachekey = 'install_' . $id;
        $cache    = \cache::make('local_customerportal', 'installationdata');

        if ($cached = $cache->get($cachekey)) {
            return $cached;
        }

        $result = $this->client->get('/v1/portal/installation/' . urlencode($id));
        $data   = $result['data'] ?? [];
        $cache->set($cachekey, $data);

        return $data;
    }

    /**
     * Fetch installed plugins for this installation.
     *
     * @return array List of plugin records.
     * @throws \moodle_exception
     */
    public function get_installed_plugins(): array {
        $id       = $this->get_installation_id();
        $cachekey = 'plugins_' . $id;
        $cache    = \cache::make('local_customerportal', 'installationdata');

        if ($cached = $cache->get($cachekey)) {
            return $cached;
        }

        $result = $this->client->get('/v1/portal/installation/' . urlencode($id) . '/plugins');
        $data   = $result['data'] ?? [];
        $cache->set($cachekey, $data);

        return $data;
    }

    /**
     * Fetch the overlay context for a single catalog entry.
     *
     * Returns an empty array if Directus has no overlay record for this combination.
     *
     * @param string $catalogentryid UUID of the catalog entry.
     * @return array Overlay fields (see blueprint §7).
     * @throws \moodle_exception
     */
    public function get_overlay(string $catalogentryid): array {
        $id       = $this->get_installation_id();
        $cachekey = 'overlay_' . $id . '_' . $catalogentryid;
        $cache    = \cache::make('local_customerportal', 'overlaydata');

        if ($cached = $cache->get($cachekey)) {
            return $cached;
        }

        try {
            $result = $this->client->get('/v1/portal/overlay', [
                'installation_id'  => $id,
                'catalog_entry_id' => $catalogentryid,
            ]);
            $data = $result['data'] ?? [];
        } catch (\moodle_exception $e) {
            // Overlay is optional — degrade gracefully if unavailable.
            $data = [];
        }

        $cache->set($cachekey, $data);
        return $data;
    }
}
