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
 * Installation service — reads local installation and plugin data.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\local;

/**
 * Provides installation data without any external API calls.
 */
class installation_service {
    /** @var string Local installation identifier. */
    private string $installationid;

    /**
     * Constructor.
     *
     * @param string|null $installationid Optional override for the local identifier.
     */
    public function __construct(?string $installationid = null) {
        $this->installationid = $installationid ?? self::build_local_installation_id();
    }

    /**
     * Returns the local installation ID.
     *
     * @return string
     */
    public function get_installation_id(): string {
        return $this->installationid;
    }

    /**
     * Return local installation summary data.
     *
     * @return array Keys: id, label, moodle_version, profile.
     */
    public function get_installation(): array {
        global $CFG, $SITE;

        $label = trim((string) get_config('local_customerportal', 'site_label'));
        if ($label === '') {
            $label = trim((string) ($SITE->fullname ?? ''));
        }
        if ($label === '') {
            $label = trim((string) format_string(\get_site()->fullname));
        }

        return [
            'id' => $this->get_installation_id(),
            'label' => $label,
            'moodle_version' => (string) ($CFG->release ?? ''),
            'profile' => (string) get_config('local_customerportal', 'profile') ?: 'managed',
        ];
    }

    /**
     * Fetch installed plugins from the local Moodle plugin manager.
     *
     * @return array List of plugin records.
     */
    public function get_installed_plugins(): array {
        $pluginman = \core_plugin_manager::instance();
        $plugins = [];

        foreach ($pluginman->get_plugins() as $typeplugins) {
            foreach ($typeplugins as $plugininfo) {
                if (empty($plugininfo->rootdir)) {
                    continue;
                }

                $plugins[] = [
                    'frankenstyle' => (string) $plugininfo->component,
                    'display_name' => (string) $plugininfo->displayname,
                    'installed_version' => (string) ($plugininfo->versiondb ?? $plugininfo->versiondisk ?? ''),
                    'plugin_type' => (string) ($plugininfo->type ?? ''),
                    'status' => 'installed',
                ];
            }
        }

        return $plugins;
    }
    /**
     * Build a stable local identifier that fits the legacy request table field.
     *
     * @return string
     */
    private static function build_local_installation_id(): string {
        global $CFG;

        return md5((string) ($CFG->wwwroot ?? 'local_customerportal'));
    }
}
