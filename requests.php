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
 * Requests page — list and create customer requests.
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

$action        = optional_param('action', 'list', PARAM_ALPHA);
$catalogentryid = optional_param('catalog_entry_id', '', PARAM_ALPHANUMEXT);

$PAGE->set_url('/local/customerportal/requests.php', ['action' => $action]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('request_heading', 'local_customerportal'));
$PAGE->set_heading(get_string('request_heading', 'local_customerportal'));
$PAGE->set_pagelayout('standard');

$requestsvc  = new \local_customerportal\local\request_service();
$cancreate   = get_capability_info('local/customerportal:createrequest')
    && has_capability('local/customerportal:createrequest', $context);

$requests    = [];
$form        = null;
$success     = false;
$error       = null;

$navdata = [
    'active_requests'  => true,
    'url_dashboard'    => (new \moodle_url('/local/customerportal/index.php'))->out(false),
    'url_installation' => (new \moodle_url('/local/customerportal/installation.php'))->out(false),
    'url_myplugins'    => (new \moodle_url('/local/customerportal/myplugins.php'))->out(false),
    'url_catalog'      => (new \moodle_url('/local/customerportal/catalog.php'))->out(false),
    'url_requests'     => (new \moodle_url('/local/customerportal/requests.php'))->out(false),
];

if ($action === 'new' && $cancreate) {
    $form = new \local_customerportal\form\request_form(
        new \moodle_url('/local/customerportal/requests.php', ['action' => 'new']),
        ['catalog_entry_id' => $catalogentryid]
    );

    if ($form->is_cancelled()) {
        redirect(new \moodle_url('/local/customerportal/requests.php'));
    } else if ($data = $form->get_data()) {
        require_sesskey();
        try {
            $requestsvc->create(
                $data->request_type,
                $data->message,
                !empty($data->catalog_entry_id) ? $data->catalog_entry_id : null
            );
            redirect(
                new \moodle_url('/local/customerportal/requests.php'),
                get_string('request_created_success', 'local_customerportal'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } catch (\moodle_exception $e) {
            $error = $e->getMessage();
        }
    }

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_customerportal/request_list', array_merge($navdata, [
        'show_form'    => true,
        'form_html'    => $form->render(),
        'has_requests' => false,
        'requests'     => [],
        'error'        => $error,
    ]));
    echo $OUTPUT->footer();
    exit;
}

try {
    $requests = $requestsvc->list_for_installation();
} catch (\moodle_exception $e) {
    $error = $e->getMessage();
}

$typemap = [
    'plugin_request'     => 'request_plugin',
    'feature_request'    => 'request_feature',
    'storage_request'    => 'request_storage',
    'consulting_request' => 'request_consulting',
];

$normalised = [];
foreach ($requests as $req) {
    $type   = is_array($req) ? ($req['request_type'] ?? '') : ($req->request_type ?? '');
    $status = is_array($req) ? ($req['status'] ?? 'pending') : ($req->status ?? 'pending');
    $msg    = is_array($req) ? ($req['message'] ?? '') : ($req->message ?? '');
    $ts     = is_array($req) ? ($req['timecreated'] ?? 0) : ($req->timecreated ?? 0);
    $normalised[] = [
        'request_type'       => $type,
        'request_type_label' => get_string($typemap[$type] ?? 'request_plugin', 'local_customerportal'),
        'message'            => $msg,
        'status'             => $status,
        'status_label'       => get_string('request_status_' . $status, 'local_customerportal'),
        'status_pending'     => $status === 'pending',
        'status_synced'      => $status === 'synced',
        'status_error'       => $status === 'error',
        'timecreated_str'    => $ts > 0 ? userdate((int)$ts) : '',
    ];
}
$requests = $normalised;

$templatedata = array_merge($navdata, [
    'show_form'    => false,
    'requests'     => $requests,
    'has_requests' => !empty($requests),
    'can_create'   => $cancreate,
    'url_new'      => (new \moodle_url('/local/customerportal/requests.php', ['action' => 'new']))->out(false),
    'error'        => $error,
]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_customerportal/request_list', $templatedata);
echo $OUTPUT->footer();
