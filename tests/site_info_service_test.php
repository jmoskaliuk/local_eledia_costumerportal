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
 * Unit tests for site_info_service.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal;

use local_customerportal\local\site_info_service;

/**
 * Tests for site_info_service — local Moodle statistics.
 *
 * @covers \local_customerportal\local\site_info_service
 */
final class site_info_service_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * get_user_stats() must return both required keys.
     */
    public function test_get_user_stats_returns_both_keys(): void {
        $svc = new site_info_service();
        $stats = $svc->get_user_stats();
        $this->assertArrayHasKey('registered', $stats);
        $this->assertArrayHasKey('active', $stats);
    }

    /**
     * Newly created user increases registered count by exactly one.
     */
    public function test_registered_count_increases_after_user_creation(): void {
        $svc = new site_info_service();
        $before = $svc->get_user_stats()['registered'];
        $this->getDataGenerator()->create_user();
        $this->assertEquals($before + 1, $svc->get_user_stats()['registered']);
    }

    /**
     * User with recent lastaccess is counted as active; user with old lastaccess is not.
     */
    public function test_active_count_respects_lastaccess_threshold(): void {
        global $DB;

        $svc = new site_info_service();
        $before = $svc->get_user_stats()['active'];

        $recent = $this->getDataGenerator()->create_user();
        $DB->update_record('user', (object) ['id' => $recent->id, 'lastaccess' => time()]);

        $old = $this->getDataGenerator()->create_user();
        $DB->update_record('user', (object) ['id' => $old->id, 'lastaccess' => time() - (60 * DAYSECS)]);

        $after = $svc->get_user_stats()['active'];
        $this->assertEquals($before + 1, $after, 'Only the recently-active user should be counted.');
    }

    /**
     * active count must never exceed registered count.
     */
    public function test_active_never_exceeds_registered(): void {
        $svc = new site_info_service();
        $stats = $svc->get_user_stats();
        $this->assertLessThanOrEqual($stats['registered'], $stats['active']);
    }

    /**
     * Creating a visible course increases get_course_count() by one.
     */
    public function test_course_count_increases_after_course_creation(): void {
        $svc = new site_info_service();
        $before = $svc->get_course_count();
        $this->getDataGenerator()->create_course(['visible' => 1]);
        $this->assertEquals($before + 1, $svc->get_course_count());
    }

    /**
     * Hidden courses must not be counted.
     */
    public function test_hidden_courses_not_counted(): void {
        $svc = new site_info_service();
        $before = $svc->get_course_count();
        $this->getDataGenerator()->create_course(['visible' => 0]);
        $this->assertEquals($before, $svc->get_course_count());
    }

    /**
     * get_moodle_release() returns a non-empty string.
     */
    public function test_get_moodle_release_returns_nonempty_string(): void {
        $svc = new site_info_service();
        $release = $svc->get_moodle_release();
        $this->assertIsString($release);
        $this->assertNotEmpty($release);
    }

    /**
     * get_cron_info() returns both required keys with correct types.
     */
    public function test_get_cron_info_returns_expected_keys(): void {
        $svc = new site_info_service();
        $info = $svc->get_cron_info();
        $this->assertArrayHasKey('lastrun', $info);
        $this->assertArrayHasKey('failed_tasks', $info);
        $this->assertIsInt($info['lastrun']);
        $this->assertIsInt($info['failed_tasks']);
    }

    /**
     * count_failed_tasks() returns a non-negative integer in a clean environment.
     */
    public function test_count_failed_tasks_is_non_negative(): void {
        $svc = new site_info_service();
        $this->assertGreaterThanOrEqual(0, $svc->count_failed_tasks());
    }
}
