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
 * My Plugins page.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = \core\context\system::instance();
if (!get_capability_info('local/customerportal:view')) {
    redirect(new \moodle_url('/'));
}
require_capability('local/customerportal:view', $context);

$PAGE->set_url('/local/customerportal/myplugins.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('myplugins_heading', 'local_customerportal'));
$PAGE->set_heading(get_string('myplugins_heading', 'local_customerportal'));
$PAGE->set_pagelayout('standard');

$installationsvc = new \local_customerportal\local\installation_service();
$plugins         = [];
$error           = null;
$hassynced       = true;

try {
    $hassynced = $installationsvc->has_synced();
    $rawplugins = $installationsvc->get_installed_plugins();

    foreach ($rawplugins as $plugin) {
        $status = (string) ($plugin['status'] ?? 'installed');
        $knownstatus = in_array($status, ['installed', 'outdated', 'deprecated', 'removed'], true);
        $slug = $plugin['slug'] ?? null;
        $plugins[] = [
            'frankenstyle'      => $plugin['frankenstyle'] ?? '',
            'display_name'      => $plugin['display_name'] ?? $plugin['frankenstyle'] ?? '',
            'installed_version' => $plugin['installed_version'] ?? '',
            'proven_badge'      => $plugin['proven_badge'] ?? null,
            'has_proven'        => !empty($plugin['proven_badge']),
            'status'            => $status,
            'status_label'      => get_string(
                'myplugins_status_' . ($knownstatus ? $status : 'installed'),
                'local_customerportal'
            ),
            'status_installed'  => $status === 'installed',
            'status_outdated'   => $status === 'outdated',
            'status_deprecated' => $status === 'deprecated',
            'status_removed'    => $status === 'removed',
            'catalog_entry_id'  => $plugin['catalog_entry_id'] ?? null,
            'has_catalog_entry' => !empty($plugin['catalog_entry_id']) && !empty($slug),
            'url_detail'        => !empty($slug)
                ? (new \moodle_url('/local/customerportal/plugin.php', ['slug' => $slug]))->out(false)
                : null,
        ];
    }
} catch (\moodle_exception $e) {
    $error = $e->getMessage();
}

$templatedata = [
    'plugins'     => $plugins,
    'has_plugins' => !empty($plugins),
    // Distinguish "synced with zero plugins" from "never synced yet" so the
    // portal can render an explicit not-yet-synchronised state (task37)
    // instead of the generic empty-list message.
    'never_synced'     => !$hassynced && empty($plugins),
    'error'            => $error,
    'active_myplugins' => true,
    'url_dashboard'    => (new \moodle_url('/local/customerportal/index.php'))->out(false),
    'url_installation' => (new \moodle_url('/local/customerportal/installation.php'))->out(false),
    'url_myplugins'    => (new \moodle_url('/local/customerportal/myplugins.php'))->out(false),
    'url_catalog'      => (new \moodle_url('/local/customerportal/catalog.php'))->out(false),
    'url_requests'     => (new \moodle_url('/local/customerportal/requests.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_customerportal/plugin_list', $templatedata);
echo $OUTPUT->footer();
