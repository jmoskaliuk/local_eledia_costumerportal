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
 * Unit tests for health_service (task33 telemetry).
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal;

use local_customerportal\local\health_service;
use local_customerportal\local\site_info_service;

/**
 * Verifies the traffic-light classifier fires the right thresholds
 * (see health_service::RED_* / AMBER_* constants).
 *
 * @covers \local_customerportal\local\health_service
 */
final class health_service_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Helper: build a site_info double that returns a scripted cron_info array.
     *
     * @param int $lastrun
     * @param int $failed
     * @return site_info_service
     */
    private function make_siteinfo(int $lastrun, int $failed): site_info_service {
        return new class ($lastrun, $failed) extends site_info_service {
            /** @var int */
            private int $lastrun;
            /** @var int */
            private int $failed;
            /**
             * Capture scripted lastrun / failed values for the stub.
             *
             * @param int $lastrun
             * @param int $failed
             */
            public function __construct(int $lastrun, int $failed) {
                $this->lastrun = $lastrun;
                $this->failed = $failed;
            }
            /**
             * Return the scripted cron info for the health classifier.
             *
             * @return array
             */
            public function get_cron_info(): array {
                return ['lastrun' => $this->lastrun, 'failed_tasks' => $this->failed];
            }
        };
    }

    /**
     * Fresh cron run with no failed tasks -> green.
     */
    public function test_green_when_cron_fresh(): void {
        $svc = new health_service($this->make_siteinfo(time() - 60, 0));
        $this->assertSame('green', $svc->get_overall_status());
    }

    /**
     * One failed task bumps to amber.
     */
    public function test_amber_on_one_failed_task(): void {
        $svc = new health_service($this->make_siteinfo(time() - 60, 1));
        $this->assertSame('amber', $svc->get_overall_status());
    }

    /**
     * Three failed tasks trip the red threshold even when cron is fresh.
     */
    public function test_red_on_three_failed_tasks(): void {
        $svc = new health_service($this->make_siteinfo(time() - 60, 3));
        $this->assertSame('red', $svc->get_overall_status());
    }

    /**
     * Stale cron beyond the 24h threshold -> red.
     */
    public function test_red_on_stale_cron(): void {
        $svc = new health_service($this->make_siteinfo(time() - (2 * DAYSECS), 0));
        $this->assertSame('red', $svc->get_overall_status());
    }

    /**
     * Cron between 2h and 24h stale -> amber (soft threshold).
     */
    public function test_amber_on_soft_stale_cron(): void {
        $svc = new health_service($this->make_siteinfo(time() - (4 * HOURSECS), 0));
        $this->assertSame('amber', $svc->get_overall_status());
    }

    /**
     * Never-run cron (lastrun=0) does not pretend the site is unhealthy.
     */
    public function test_green_when_cron_never_ran(): void {
        $svc = new health_service($this->make_siteinfo(0, 0));
        $this->assertSame('green', $svc->get_overall_status());
    }
}
