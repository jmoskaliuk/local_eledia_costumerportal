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
require_once(__DIR__ . '/lib.php');

require_login();
$context = \core\context\system::instance();
if (!get_capability_info('local/customerportal:view')) {
    redirect(new \moodle_url('/'));
}
require_capability('local/customerportal:view', $context);

$PAGE->set_url('/local/customerportal/myplugins.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('myplugins_heading', 'local_customerportal'));
$PAGE->set_heading('');
$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('lh-plugin-shell-page');

$installationsvc = new \local_customerportal\local\installation_service();
$pluginman       = \core_plugin_manager::instance();
$plugingroups    = [];
$error           = null;

try {
    $rawplugins = $installationsvc->get_installed_plugins();

    foreach ($rawplugins as $plugin) {
        $frankenstyle = trim((string) ($plugin['frankenstyle'] ?? ''));
        $plugininfo = $frankenstyle !== '' ? $pluginman->get_plugin_info($frankenstyle) : null;

        // Show only add-ons here. Standard Moodle plugins belong to core, not "My Plugins".
        if ($plugininfo && $plugininfo->is_standard()) {
            continue;
        }

        $status = (string) ($plugin['status'] ?? 'installed');
        $knownstatus = in_array($status, ['installed', 'outdated', 'deprecated', 'removed'], true);
        $plugintype = clean_param((string) ($plugin['plugin_type'] ?? ''), PARAM_ALPHANUMEXT);
        if ($plugintype === '') {
            $plugintype = $plugininfo->type ?? '';
        }
        if ($plugintype === '' && strpos($frankenstyle, '_') !== false) {
            [$plugintype] = explode('_', $frankenstyle, 2);
        }
        if ($plugintype === '') {
            $plugintype = 'other';
        }

        try {
            $typelabel = $plugintype === 'other'
                ? get_string('myplugins_type_other', 'local_customerportal')
                : $pluginman->plugintype_name_plural($plugintype);
        } catch (\Throwable $e) {
            $typelabel = get_string('myplugins_type_other', 'local_customerportal');
        }

        if (!isset($plugingroups[$plugintype])) {
            $plugingroups[$plugintype] = [
                'type' => $plugintype,
                'type_label' => $typelabel,
                'plugins' => [],
            ];
        }

        $plugingroups[$plugintype]['plugins'][] = [
            'frankenstyle'      => $frankenstyle,
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
        ];
    }

    foreach ($plugingroups as &$group) {
        usort($group['plugins'], static function(array $left, array $right): int {
            return strnatcasecmp($left['display_name'], $right['display_name']);
        });
    }
    unset($group);

    uasort($plugingroups, static function(array $left, array $right): int {
        return strnatcasecmp($left['type_label'], $right['type_label']);
    });
} catch (\moodle_exception $e) {
    $error = $e->getMessage();
}

$templatedata = [
    'plugin_groups' => array_values($plugingroups),
    'has_plugins'   => !empty($plugingroups),
    'error'            => $error,
    'active_myplugins' => true,
    'section_title'    => get_string('nav_myplugins', 'local_customerportal'),
    'url_dashboard'    => (new \moodle_url('/local/customerportal/index.php'))->out(false),
    'url_installation' => (new \moodle_url('/local/customerportal/installation.php'))->out(false),
    'url_myplugins'    => (new \moodle_url('/local/customerportal/myplugins.php'))->out(false),
    'url_catalog'      => (new \moodle_url('/local/customerportal/catalog.php'))->out(false),
    'url_requests'     => local_customerportal_support_url(),
    'url_upgrade'      => (new \moodle_url('/local/customerportal/installation.php'))->out(false),
    'url_ai'           => local_customerportal_ai_url(),
    'has_ai' => local_customerportal_ai_url() !== '',
    'url_ai_setup'     => (new \moodle_url('/local/customerportal/ai.php'))->out(false),
    'url_invoices'     => local_customerportal_support_url(),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_customerportal/plugin_list', $templatedata);
echo $OUTPUT->footer();
