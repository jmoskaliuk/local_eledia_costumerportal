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
 * My Installation page.
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

$PAGE->set_url('/local/customerportal/installation.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('installation_heading', 'local_customerportal'));
$PAGE->set_heading(get_string('installation_heading', 'local_customerportal'));
$PAGE->set_pagelayout('standard');

$installationsvc = new \local_customerportal\local\installation_service();
$siteinfosvc     = new \local_customerportal\local\site_info_service();

$installation = [];
$error        = null;
$installationid = $installationsvc->get_optional_installation_id();
$registrationsucceeded = !empty(get_config('local_customerportal', 'installation_registered'));
$lastregistrationat = (int) get_config('local_customerportal', 'last_registration_at');
$canregisterinstallation = is_siteadmin();

if ($installationsvc->has_installation_id()) {
    try {
        $installation = $installationsvc->get_installation();
    } catch (\moodle_exception $e) {
        $error = $e->getMessage();
    }
}

$userstats = $siteinfosvc->get_user_stats();
$croninfo  = $siteinfosvc->get_cron_info();
$lastrun   = $croninfo['lastrun'];
$haslastsync = !empty($installation['last_sync_at']);

if ($installationid === '') {
    $registrationstatus = get_string('installation_registration_missing', 'local_customerportal');
    $syncstatus = get_string('installation_sync_missing', 'local_customerportal');
} else if ($registrationsucceeded) {
    $registrationstatus = get_string('installation_registration_registered', 'local_customerportal');
    $syncstatus = $haslastsync
        ? get_string('installation_sync_current', 'local_customerportal')
        : get_string('installation_sync_pending', 'local_customerportal');
} else {
    $registrationstatus = get_string('installation_registration_pending', 'local_customerportal');
    $syncstatus = get_string('installation_sync_pending', 'local_customerportal');
}

$templatedata = [
    'installation'    => $installation,
    'error'           => $error,
    'active_installation' => true,
    'url_dashboard'    => (new \moodle_url('/local/customerportal/index.php'))->out(false),
    'url_installation' => (new \moodle_url('/local/customerportal/installation.php'))->out(false),
    'url_myplugins'    => (new \moodle_url('/local/customerportal/myplugins.php'))->out(false),
    'url_catalog'      => (new \moodle_url('/local/customerportal/catalog.php'))->out(false),
    'url_requests'     => (new \moodle_url('/local/customerportal/requests.php'))->out(false),
    'siteinfo_release'    => $siteinfosvc->get_moodle_release(),
    'siteinfo_registered' => $userstats['registered'],
    'siteinfo_active'     => $userstats['active'],
    'siteinfo_courses'    => $siteinfosvc->get_course_count(),
    'siteinfo_lastcron'   => $lastrun > 0 ? userdate($lastrun) : get_string('never', 'local_customerportal'),
    'siteinfo_cron_ok'    => $lastrun > 0 && (time() - $lastrun) < 900,
    'siteinfo_failed'     => $croninfo['failed_tasks'],
    'siteinfo_has_failed' => $croninfo['failed_tasks'] > 0,
    'can_register_installation' => $canregisterinstallation,
    'has_installation_id' => $installationid !== '',
    'installation_id' => $installationid,
    'installation_registration_status_text' => $registrationstatus,
    'installation_sync_status_text' => $syncstatus,
    'installation_last_registration_at' => $lastregistrationat > 0
        ? userdate($lastregistrationat)
        : get_string('never', 'local_customerportal'),
    'registration_action' => (new \moodle_url('/local/customerportal/register_installation.php'))->out(false),
    'registration_button_text' => get_string(
        $installationid === '' ? 'installation_register_button' : 'installation_reregister_button',
        'local_customerportal'
    ),
    'sesskey' => sesskey(),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_customerportal/installation_view', $templatedata);
echo $OUTPUT->footer();
