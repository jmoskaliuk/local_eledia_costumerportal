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
 * Manual installation registration endpoint.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new \moodle_exception('invalidrequest');
}

require_login();
require_sesskey();
require_admin();

$service = new \local_customerportal\local\sync_service();
$result = $service->register_installation();
$redirecturl = new \moodle_url('/local/customerportal/installation.php');

$notificationlevel = \core\output\notification::NOTIFY_ERROR;
if ($result['success']) {
    $notificationlevel = $result['level'] === 'warning'
        ? \core\output\notification::NOTIFY_WARNING
        : \core\output\notification::NOTIFY_SUCCESS;
}

redirect($redirecturl, $result['message'], 0, $notificationlevel);
