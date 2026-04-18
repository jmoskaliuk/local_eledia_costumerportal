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
 * Plugin Catalog page.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = \core\context\system::instance();
require_capability('local/customerportal:view', $context);

$q          = optional_param('q', '', PARAM_TEXT);
$entrytype  = optional_param('entry_type', '', PARAM_ALPHANUMEXT);
$plugintype = optional_param('plugin_type', '', PARAM_ALPHANUMEXT);
$page       = optional_param('page', 1, PARAM_INT);

$PAGE->set_url('/local/customerportal/catalog.php', [
    'q'          => $q,
    'entry_type' => $entrytype,
    'plugin_type' => $plugintype,
    'page'       => $page,
]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('catalog_heading', 'local_customerportal'));
$PAGE->set_heading(get_string('catalog_heading', 'local_customerportal'));
$PAGE->set_pagelayout('standard');

$catalogsvc      = new \local_customerportal\local\catalog_service();
$installationsvc = new \local_customerportal\local\installation_service();

$entries = [];
$meta    = [];
$error   = null;

$filters = array_filter([
    'q'          => $q,
    'entry_type' => $entrytype,
    'plugin_type' => $plugintype,
    'page'       => $page,
    'page_size'  => 20,
]);

try {
    $result  = $catalogsvc->search($filters);
    $rawdata = $result['data'] ?? [];
    $meta    = $result['meta'] ?? [];

    foreach ($rawdata as $entry) {
        $entryid = $entry['id'] ?? '';
        $overlay = [];
        if (!empty($entryid)) {
            $overlay = $installationsvc->get_overlay($entryid);
        }
        $entries[] = [
            'id'              => $entryid,
            'slug'            => $entry['slug'] ?? '',
            'title'           => $entry['title'] ?? '',
            'entry_type'      => $entry['entry_type'] ?? '',
            'teaser'          => $entry['teaser'] ?? '',
            'proven_badge'    => $entry['proven_badge'] ?? null,
            'has_proven'      => !empty($entry['proven_badge']) && $entry['proven_badge'] !== 'none',
            'is_installed'    => !empty($overlay['is_installed']),
            'recommended'     => !empty($overlay['recommended']),
            'url_detail'      => (new \moodle_url(
                '/local/customerportal/plugin.php',
                ['slug' => $entry['slug'] ?? '']
            ))->out(false),
        ];
    }
} catch (\moodle_exception $e) {
    $error = $e->getMessage();
}

$templatedata = [
    'entries'     => $entries,
    'has_entries' => !empty($entries),
    'meta'        => $meta,
    'q'           => $q,
    'error'       => $error,
    'active_catalog' => true,
    'url_dashboard'    => (new \moodle_url('/local/customerportal/index.php'))->out(false),
    'url_installation' => (new \moodle_url('/local/customerportal/installation.php'))->out(false),
    'url_myplugins'    => (new \moodle_url('/local/customerportal/myplugins.php'))->out(false),
    'url_catalog'      => (new \moodle_url('/local/customerportal/catalog.php'))->out(false),
    'url_requests'     => (new \moodle_url('/local/customerportal/requests.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_customerportal/catalog_list', $templatedata);
echo $OUTPUT->footer();
