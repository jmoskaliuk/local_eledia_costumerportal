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
 * Plugin detail page (public catalog + installation overlay).
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

$slug = required_param('slug', PARAM_ALPHANUMEXT);

$PAGE->set_url('/local/customerportal/plugin.php', ['slug' => $slug]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('plugin_heading', 'local_customerportal'));
$PAGE->set_heading(get_string('plugin_heading', 'local_customerportal'));
$PAGE->set_pagelayout('standard');

$catalogsvc      = new \local_customerportal\local\catalog_service();
$installationsvc = new \local_customerportal\local\installation_service();

$entry   = [];
$overlay = [];
$error   = null;

try {
    $entry = $catalogsvc->get_detail($slug);

    if (!empty($entry['id'])) {
        $overlay = $installationsvc->get_overlay($entry['id']);
    }
} catch (\moodle_exception $e) {
    $error = $e->getMessage();
}

$cancreate = get_capability_info('local/customerportal:createrequest')
    && has_capability('local/customerportal:createrequest', $context);

$templatedata = [
    'entry'           => $entry,
    'has_entry'       => !empty($entry),
    'overlay'         => $overlay,
    'has_overlay'     => !empty($overlay),
    'can_create_request' => $cancreate && !empty($overlay['requestable']),
    'url_request'     => (new \moodle_url('/local/customerportal/requests.php', [
        'catalog_entry_id' => $entry['id'] ?? '',
        'action'           => 'new',
    ]))->out(false),
    'error'           => $error,
    'active_catalog'  => true,
    'url_dashboard'    => (new \moodle_url('/local/customerportal/index.php'))->out(false),
    'url_installation' => (new \moodle_url('/local/customerportal/installation.php'))->out(false),
    'url_myplugins'    => (new \moodle_url('/local/customerportal/myplugins.php'))->out(false),
    'url_catalog'      => (new \moodle_url('/local/customerportal/catalog.php'))->out(false),
    'url_requests'     => (new \moodle_url('/local/customerportal/requests.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_customerportal/catalog_detail', $templatedata);
echo $OUTPUT->footer();
