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
if (!get_capability_info('local/customerportal:view')) {
    redirect(new \moodle_url('/'));
}
require_capability('local/customerportal:view', $context);

$PAGE->set_url('/local/customerportal/index.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('dashboard_heading', 'local_customerportal'));
$PAGE->set_heading('');
$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('lh-plugin-shell-page');
$PAGE->requires->css('/local/lernhive/styles.css');

$installationsvc = new \local_customerportal\local\installation_service();
$requestsvc      = new \local_customerportal\local\request_service();
$siteinfosvc     = new \local_customerportal\local\site_info_service();

$installation   = [];
$openplugincount = 0;
$openrequests   = [];
$error          = null;

try {
    $installation    = $installationsvc->get_installation();
    $plugins         = $installationsvc->get_installed_plugins();
    $openplugincount = count($plugins);
    $openrequests    = $requestsvc->list_for_installation();
    $openrequests    = array_filter($openrequests, fn($r) => ($r->status ?? '') !== 'done');
} catch (\moodle_exception $e) {
    $error = $e->getMessage();
}

$userstats = $siteinfosvc->get_user_stats();
$courses   = $siteinfosvc->get_course_count();
$release   = $siteinfosvc->get_moodle_release();

$templatedata = [
    'installation'        => $installation,
    'plugin_count'        => $openplugincount,
    'open_request_count'  => count($openrequests),
    'error'               => $error,
    'url_dashboard'       => (new \moodle_url('/local/customerportal/index.php'))->out(false),
    'url_installation'    => (new \moodle_url('/local/customerportal/installation.php'))->out(false),
    'url_myplugins'       => (new \moodle_url('/local/customerportal/myplugins.php'))->out(false),
    'url_catalog'         => (new \moodle_url('/local/customerportal/catalog.php'))->out(false),
    'url_requests'        => 'https://eledia.de/kontakt',
    'url_upgrade'         => (new \moodle_url('/local/customerportal/installation.php'))->out(false),
    'url_ai'              => (new \moodle_url('/local/lernhive_ai/index.php'))->out(false),
    'url_ai_setup'        => (new \moodle_url('/local/customerportal/ai.php'))->out(false),
    'url_invoices'        => 'https://eledia.de/kontakt',
    'active_dashboard'    => true,
    'section_title'       => get_string('dashboard_intro', 'local_customerportal'),
    'siteinfo_registered' => $userstats['registered'],
    'siteinfo_active'     => $userstats['active'],
    'siteinfo_courses'    => $courses,
    'siteinfo_release'    => $release,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_customerportal/dashboard', $templatedata);
echo $OUTPUT->footer();
