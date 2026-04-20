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
 * Scheduled task — push the installation digital-twin snapshot to Directus.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\task;

use local_customerportal\local\sync_service;

/**
 * Thin cron wrapper around {@see sync_service::sync_snapshot}.
 *
 * Keeping the cron entry separate from the writer service lets the test
 * suite drive sync_service directly and lets the cron entry stay dumb.
 */
class sync_installation_snapshot extends \core\task\scheduled_task {
    /** @var sync_service|null */
    private ?sync_service $service = null;

    /**
     * Inject a sync_service for testing.
     *
     * @param sync_service|null $service
     */
    public function set_sync_service(?sync_service $service): void {
        $this->service = $service;
    }

    /**
     * Return the task name shown in the Moodle scheduler.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_sync_installation_snapshot', 'local_customerportal');
    }

    /**
     * Execute the task. Never throws — cron keeps making forward progress
     * on other tasks even when Directus is temporarily unreachable.
     */
    public function execute(): void {
        if ((string) get_config('local_customerportal', 'installation_id') === '') {
            mtrace('[local_customerportal] snapshot skipped: installation_id not configured.');
            return;
        }

        $service = $this->service ?? new sync_service();
        $ok = $service->sync_snapshot();

        if ($ok) {
            mtrace('[local_customerportal] snapshot sync OK.');
        } else {
            mtrace('[local_customerportal] snapshot sync failed (will retry next cron tick).');
        }
    }
}
