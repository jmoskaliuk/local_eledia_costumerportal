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
 * Dashboard page.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = \core\context\system::instance();
require_capability('local/customerportal:view', $context);

$PAGE->set_url('/local/customerportal/index.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('dashboard_heading', 'local_customerportal'));
$PAGE->set_heading(get_string('dashboard_heading', 'local_customerportal'));
$PAGE->set_pagelayout('standard');

$installationsvc = new \local_customerportal\local\installation_service();
$requestsvc      = new \local_customerportal\local\request_service();
$catalogsvc      = new \local_customerportal\local\catalog_service();

$installation   = [];
$openplugincount = 0;
$openrequests   = [];
$recommendations = [];
$error          = null;

try {
    $installation    = $installationsvc->get_installation();
    $plugins         = $installationsvc->get_installed_plugins();
    $openplugincount = count($plugins);
    $openrequests    = $requestsvc->list_for_installation();
    $openrequests    = array_filter($openrequests, fn($r) => ($r['status'] ?? '') !== 'done');

    $catalogresult  = $catalogsvc->search(['page_size' => 10]);
    $allentries     = $catalogresult['data'] ?? [];

    foreach ($allentries as $entry) {
        $entryid = $entry['id'] ?? '';
        if (empty($entryid)) {
            continue;
        }
        $overlay = $installationsvc->get_overlay($entryid);
        if (!empty($overlay['recommended'])) {
            $entry['overlay'] = $overlay;
            $recommendations[] = $entry;
            if (count($recommendations) >= 3) {
                break;
            }
        }
    }
} catch (\moodle_exception $e) {
    $error = $e->getMessage();
}

$templatedata = [
    'installation'       => $installation,
    'plugin_count'       => $openplugincount,
    'open_request_count' => count($openrequests),
    'recommendations'    => $recommendations,
    'has_recommendations' => !empty($recommendations),
    'error'              => $error,
    'url_installation'   => (new \moodle_url('/local/customerportal/installation.php'))->out(false),
    'url_myplugins'      => (new \moodle_url('/local/customerportal/myplugins.php'))->out(false),
    'url_catalog'        => (new \moodle_url('/local/customerportal/catalog.php'))->out(false),
    'url_requests'       => (new \moodle_url('/local/customerportal/requests.php'))->out(false),
    'active_tab'         => 'dashboard',
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_customerportal/dashboard', $templatedata);
echo $OUTPUT->footer();
