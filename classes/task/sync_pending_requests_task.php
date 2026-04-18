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
 * Scheduled task: sync pending requests to Directus.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\task;

use local_customerportal\local\request_service;

/**
 * Pushes locally stored pending requests to Directus every 5 minutes.
 */
class sync_pending_requests_task extends \core\task\scheduled_task {
    /**
     * Return task display name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('pluginname', 'local_customerportal') . ': sync pending requests';
    }

    /**
     * Execute the task — push pending requests to Directus.
     */
    public function execute(): void {
        global $DB;

        $pending = $DB->get_records(
            'local_customerportal_request',
            ['status' => 'pending'],
            'timecreated ASC',
            '*',
            0,
            50
        );

        if (empty($pending)) {
            mtrace('No pending requests to sync.');
            return;
        }

        $svc     = new request_service();
        $synced  = 0;
        $failed  = 0;

        foreach ($pending as $record) {
            if ($svc->sync_to_directus($record)) {
                $synced++;
            } else {
                $failed++;
            }
        }

        mtrace("Synced: {$synced}, failed: {$failed}.");
    }
}
