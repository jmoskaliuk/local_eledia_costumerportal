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
 * Health service — aggregates local site signals into a single traffic-light.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\local;

/**
 * Derives a `green|amber|red` overall health value from locally observable
 * signals (cron freshness + failed task count).
 *
 * The classifier is deliberately conservative:
 * - red when cron has not run in the last 24h OR >= 3 tasks are stuck
 * - amber when cron has not run in the last 2h OR >= 1 task is stuck
 * - green otherwise (includes the "no cron run yet" edge case on fresh sites)
 *
 * The snapshot sync task ships this value as `health_overall`; the field is
 * optional on the wire so a site without a signal returns no value, not a
 * misleading `green`.
 */
class health_service {
    /** @var int Hard threshold in seconds: if `lastcronruntime` is older, health is red. */
    public const RED_CRON_STALENESS = DAYSECS;

    /** @var int Soft threshold in seconds: if `lastcronruntime` is older, health is amber. */
    public const AMBER_CRON_STALENESS = 2 * HOURSECS;

    /** @var int Failed-task count that already drops health to red. */
    public const RED_FAILED_TASKS = 3;

    /** @var site_info_service */
    private site_info_service $siteinfo;

    /**
     * Constructor.
     *
     * @param site_info_service|null $siteinfo Optional override for testing.
     */
    public function __construct(?site_info_service $siteinfo = null) {
        $this->siteinfo = $siteinfo ?? new site_info_service();
    }

    /**
     * Classify current site health.
     *
     * @return string One of `green`, `amber`, `red`.
     */
    public function get_overall_status(): string {
        $cron = $this->siteinfo->get_cron_info();
        $lastrun = (int) ($cron['lastrun'] ?? 0);
        $failed = (int) ($cron['failed_tasks'] ?? 0);
        $now = time();

        $cronage = $lastrun > 0 ? $now - $lastrun : 0;

        if ($failed >= self::RED_FAILED_TASKS) {
            return 'red';
        }
        if ($lastrun > 0 && $cronage > self::RED_CRON_STALENESS) {
            return 'red';
        }
        if ($failed >= 1) {
            return 'amber';
        }
        if ($lastrun > 0 && $cronage > self::AMBER_CRON_STALENESS) {
            return 'amber';
        }
        return 'green';
    }
}
