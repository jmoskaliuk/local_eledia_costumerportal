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
 * Site info service — reads local Moodle statistics from the database.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\local;

/**
 * Provides local Moodle site statistics without any external API calls.
 */
class site_info_service {
    /** @var int Days used to determine whether a user is considered active. */
    public const ACTIVE_DAYS = 30;

    /**
     * Returns registered and active user counts.
     *
     * Registered: confirmed, not deleted, not the guest account.
     * Active: registered users who logged in within the last ACTIVE_DAYS days.
     *
     * @return array Keys: registered (int), active (int).
     */
    public function get_user_stats(): array {
        global $DB;

        $base       = "deleted = 0 AND confirmed = 1 AND username != 'guest'";
        $registered = (int) $DB->count_records_select('user', $base);

        $threshold = time() - (self::ACTIVE_DAYS * DAYSECS);
        $active    = (int) $DB->count_records_select(
            'user',
            $base . ' AND lastaccess > :threshold',
            ['threshold' => $threshold]
        );

        return ['registered' => $registered, 'active' => $active];
    }

    /**
     * Returns the count of visible courses, excluding the top-level site course.
     *
     * @return int
     */
    public function get_course_count(): int {
        global $DB;
        return (int) $DB->count_records_select(
            'course',
            'visible = 1 AND id != :siteid',
            ['siteid' => SITEID]
        );
    }

    /**
     * Returns the local Moodle release string (e.g. "5.1.3+ (Build: 20260403)").
     *
     * @return string
     */
    public function get_moodle_release(): string {
        global $CFG;
        return $CFG->release;
    }

    /**
     * Returns cron health information.
     *
     * @return array Keys: lastrun (int Unix timestamp, 0 if never), failed_tasks (int).
     */
    public function get_cron_info(): array {
        return [
            'lastrun'      => (int) get_config('core', 'lastcronruntime'),
            'failed_tasks' => $this->count_failed_tasks(),
        ];
    }

    /**
     * Returns the total number of tasks currently in a failed state.
     *
     * Counts both scheduled and ad-hoc tasks with a non-zero fail delay.
     *
     * @return int
     */
    public function count_failed_tasks(): int {
        global $DB;
        $scheduled = (int) $DB->count_records_select('task_scheduled', 'faildelay > 0');
        $adhoc     = (int) $DB->count_records_select('task_adhoc', 'faildelay > 0');
        return $scheduled + $adhoc;
    }
}
