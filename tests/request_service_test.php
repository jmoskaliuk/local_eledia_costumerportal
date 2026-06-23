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
 * Unit tests for request_service.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal;

use local_customerportal\local\request_service;

/**
 * Tests for request_service — covers test03 (request creation flow).
 *
 * @covers \local_customerportal\local\request_service
 */
final class request_service_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * All four required request types must be present in the constant.
     */
    public function test_request_types_constant_contains_expected_values(): void {
        $this->assertContains('plugin_request', request_service::REQUEST_TYPES);
        $this->assertContains('feature_request', request_service::REQUEST_TYPES);
        $this->assertContains('storage_request', request_service::REQUEST_TYPES);
        $this->assertContains('consulting_request', request_service::REQUEST_TYPES);
        $this->assertCount(4, request_service::REQUEST_TYPES);
    }

    public function test_create_throws_for_invalid_request_type(): void {
        $this->setUser($this->getDataGenerator()->create_user());
        $svc = new request_service();

        try {
            $svc->create('invalid_type', 'This is a test message.');
            $this->fail('Expected moodle_exception was not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertEquals('error_request_failed', $e->errorcode);
        }
    }

    /**
     * create() stores a local DB record with status=local.
     */
    public function test_create_stores_local_record_in_db(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $svc = new request_service();
        $id  = $svc->create('plugin_request', 'Please install the attendance plugin for our site.');

        $this->assertGreaterThan(0, $id);

        $record = $DB->get_record('local_customerportal_request', ['id' => $id], '*', MUST_EXIST);
        $this->assertNotEmpty($record->installation_id);
        $this->assertEquals('plugin_request', $record->request_type);
        $this->assertEquals('local', $record->status);
        $this->assertEquals($user->id, (int) $record->userid);
        $this->assertGreaterThan(0, $record->timecreated);
    }

    public function test_create_accepts_request_without_related_context(): void {
        global $DB;

        $this->setUser($this->getDataGenerator()->create_user());
        $svc = new request_service();

        $id = $svc->create('consulting_request', 'We would like a general Moodle consulting session.');

        $record = $DB->get_record('local_customerportal_request', ['id' => $id], '*', MUST_EXIST);
        $this->assertEquals('consulting_request', $record->request_type);
    }
}
