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
 * Unit tests for catalog_service.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal;

use local_customerportal\local\api_client;
use local_customerportal\local\catalog_service;

/**
 * Tests for catalog_service behaviour used by task29.
 *
 * @covers \local_customerportal\local\catalog_service
 */
final class catalog_service_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * get_detail() must expose runbot_demo_id from API payload.
     */
    public function test_get_detail_forwards_runbot_demo_id(): void {
        $fakeclient = new class (['data' => [
            'id' => 1,
            'slug' => 'attendance-plus',
            'title' => 'Attendance Plus',
            'runbot_demo_id' => ' exam2pdf ',
        ]]) extends api_client {
            /** @var array */
            private array $payload;

            /**
             * Constructor.
             *
             * @param array $payload
             */
            public function __construct(array $payload) {
                $this->payload = $payload;
            }

            /**
             * Return the mocked payload.
             *
             * @param string $path
             * @param array $params
             * @return array
             */
            public function catalog_get(string $path, array $params = []): array {
                return $this->payload;
            }
        };
        $service = new catalog_service($fakeclient);

        $detail = $service->get_detail('attendance-plus');

        $this->assertArrayHasKey('runbot_demo_id', $detail);
        $this->assertSame('exam2pdf', $detail['runbot_demo_id']);
    }

    /**
     * get_detail() should return runbot_demo_id as null when absent.
     */
    public function test_get_detail_sets_runbot_demo_id_to_null_when_missing(): void {
        $fakeclient = new class (['data' => [
            'id' => 2,
            'slug' => 'attendance-plus-pro',
            'title' => 'Attendance Plus Pro',
        ]]) extends api_client {
            /** @var array */
            private array $payload;

            /**
             * Constructor.
             *
             * @param array $payload
             */
            public function __construct(array $payload) {
                $this->payload = $payload;
            }

            /**
             * Return the mocked payload.
             *
             * @param string $path
             * @param array $params
             * @return array
             */
            public function catalog_get(string $path, array $params = []): array {
                return $this->payload;
            }
        };
        $service = new catalog_service($fakeclient);

        $detail = $service->get_detail('attendance-plus-pro');

        $this->assertArrayHasKey('runbot_demo_id', $detail);
        $this->assertNull($detail['runbot_demo_id']);
    }

    /**
     * task26: classification filters must pass through to api_client verbatim.
     */
    public function test_search_forwards_classification_filters(): void {
        $recordingclient = new class (['data' => [], 'meta' => ['total' => 0]]) extends api_client {
            /** @var array */
            private array $payload;
            /** @var array<int,array{path:string,params:array}> */
            public array $calls = [];

            /**
             * Constructor.
             *
             * @param array $payload
             */
            public function __construct(array $payload) {
                $this->payload = $payload;
            }

            /**
             * Record the request and return the mocked payload.
             *
             * @param string $path
             * @param array $params
             * @return array
             */
            public function catalog_get(string $path, array $params = []): array {
                $this->calls[] = ['path' => $path, 'params' => $params];
                return $this->payload;
            }
        };
        $service = new catalog_service($recordingclient);

        $service->search([
            'q'                 => 'attendance',
            'pricing_model'     => 'paid_subscription',
            'maintenance_badge' => 'actively_maintained',
            'gdpr_readiness'    => 'green',
            'is_open_source'    => 'false',
            'is_eledia_product' => 'true',
        ]);

        $this->assertCount(1, $recordingclient->calls);
        $params = $recordingclient->calls[0]['params'];
        $this->assertSame('paid_subscription', $params['pricing_model']);
        $this->assertSame('actively_maintained', $params['maintenance_badge']);
        $this->assertSame('green', $params['gdpr_readiness']);
        $this->assertSame('false', $params['is_open_source']);
        $this->assertSame('true', $params['is_eledia_product']);
        $this->assertSame('/search', $recordingclient->calls[0]['path']);
    }

    /**
     * get_detail() must support slugs with hyphens when cache uses simple keys.
     */
    public function test_get_detail_accepts_hyphenated_slug(): void {
        $fakeclient = new class (['data' => [
            'id' => 3,
            'slug' => 'attendance-plus-pro',
            'title' => 'Attendance Plus Pro',
        ]]) extends api_client {
            /** @var array */
            private array $payload;

            /**
             * Constructor.
             *
             * @param array $payload
             */
            public function __construct(array $payload) {
                $this->payload = $payload;
            }

            /**
             * Return the mocked payload.
             *
             * @param string $path
             * @param array $params
             * @return array
             */
            public function catalog_get(string $path, array $params = []): array {
                return $this->payload;
            }
        };
        $service = new catalog_service($fakeclient);

        $detail = $service->get_detail('attendance-plus-pro');

        $this->assertSame('attendance-plus-pro', $detail['slug']);
    }
}
