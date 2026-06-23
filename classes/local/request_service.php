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
 * Request service — creates and retrieves customer requests.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\local;

/**
 * Handles customer request creation and retrieval in the local Moodle database.
 */
class request_service {
    /** @var installation_service */
    private installation_service $installationsvc;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->installationsvc = new installation_service();
    }

    /**
     * Valid request types.
     */
    public const REQUEST_TYPES = [
        'plugin_request',
        'feature_request',
        'storage_request',
        'consulting_request',
    ];

    /**
     * Create a new local request.
     *
     * @param string      $requesttype  One of self::REQUEST_TYPES.
     * @param string      $message      Customer message (min 20 chars enforced by form).
     * @return int Local DB record ID.
     * @throws \moodle_exception If the request type is invalid.
     */
    public function create(string $requesttype, string $message): int {
        global $DB, $USER;

        if (!in_array($requesttype, self::REQUEST_TYPES, true)) {
            throw new \moodle_exception('error_request_failed', 'local_customerportal');
        }

        $installationid = $this->installationsvc->get_installation_id();

        $record = (object) [
            'installation_id'  => $installationid,
            'request_type'     => $requesttype,
            'message'          => $message,
            'userid'           => $USER->id,
            'status'           => 'local',
            'timecreated'      => time(),
            'timemodified'     => time(),
        ];

        return $DB->insert_record('local_customerportal_request', $record);
    }

    /**
     * List requests for the current local installation.
     *
     * @return array List of request records.
     */
    public function list_for_installation(): array {
        global $DB;

        $records = $DB->get_records(
            'local_customerportal_request',
            ['installation_id' => $this->installationsvc->get_installation_id()],
            'timecreated DESC'
        );

        return array_values($records);
    }
}
