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
 * Privacy API implementation.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for local_customerportal.
 * Stores: userid, request_type, message, timecreated in local_customerportal_request.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe personal data stored in this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_customerportal_request',
            [
                'userid'       => 'privacy:metadata:local_customerportal_request:userid',
                'request_type' => 'privacy:metadata:local_customerportal_request:request_type',
                'message'      => 'privacy:metadata:local_customerportal_request:message',
                'timecreated'  => 'privacy:metadata:local_customerportal_request:timecreated',
            ],
            'privacy:metadata:local_customerportal_request'
        );
        return $collection;
    }

    /**
     * Get contexts containing data for the given user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * Get users who have data in the given context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \core\context\system) {
            return;
        }
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {local_customerportal_request}',
            []
        );
    }

    /**
     * Export personal data for the given user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $records = $DB->get_records('local_customerportal_request', ['userid' => $userid]);

        if (empty($records)) {
            return;
        }

        $context = \core\context\system::instance();
        writer::with_context($context)->export_data(
            [get_string('pluginname', 'local_customerportal')],
            (object) ['requests' => array_values($records)]
        );
    }

    /**
     * Delete all data for all users in the given context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context instanceof \core\context\system) {
            $DB->delete_records('local_customerportal_request');
        }
    }

    /**
     * Delete data for a specific user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $DB->delete_records('local_customerportal_request', ['userid' => $contextlist->get_user()->id]);
    }

    /**
     * Delete data for multiple users.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \core\context\system) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_customerportal_request', "userid {$insql}", $params);
    }
}
