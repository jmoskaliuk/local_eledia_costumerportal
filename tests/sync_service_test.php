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
 * Unit tests for sync_service.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal;

use local_customerportal\local\api_client;
use local_customerportal\local\installation_service;
use local_customerportal\local\sync_service;

/**
 * Tests for snapshot and plugin sync payload normalization.
 *
 * @covers \local_customerportal\local\sync_service
 */
final class sync_service_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Moodle release strings must be reduced to major.minor.
     */
    public function test_normalize_moodle_version_returns_major_minor(): void {
        $service = new sync_service($this->create_passthrough_client(), $this->create_installation_service(), 0);

        $this->assertSame('4.5', $service->normalize_moodle_version('4.5.1+ (Build: 20250414)'));
        $this->assertSame('4.6', $service->normalize_moodle_version(' 4.6dev '));
    }

    /**
     * Frankenstyle normalization must trim and lowercase values.
     */
    public function test_normalize_frankenstyle_trims_and_lowercases(): void {
        $service = new sync_service($this->create_passthrough_client(), $this->create_installation_service(), 0);

        $this->assertSame('mod_quiz', $service->normalize_frankenstyle(' MOD_Quiz '));
    }

    /**
     * Snapshot sync must fail softly on HTTP errors.
     */
    public function test_sync_snapshot_returns_false_on_api_failure(): void {
        $failingclient = new class () extends api_client {
            /**
             * Constructor intentionally skips parent config loading.
             */
            public function __construct() {
            }

            /**
             * Always fail to simulate a 500-like API error.
             *
             * @param string $path
             * @param array $body
             * @return array
             * @throws \moodle_exception
             */
            public function post(string $path, array $body): array {
                throw new \moodle_exception('error_api_unavailable', 'local_customerportal', '', null, 'HTTP 500');
            }
        };

        $service = new sync_service($failingclient, $this->create_installation_service(), 0);

        $this->assertFalse($service->sync_snapshot());
    }

    /**
     * Build snapshot payload should reflect plugin config booleans and installation id.
     */
    public function test_build_snapshot_payload_reads_config(): void {
        global $SITE, $CFG;

        set_config('flavour', 'workplace', 'local_customerportal');
        set_config('release_channel', 'newest', 'local_customerportal');
        set_config('sla_level', 'level_2', 'local_customerportal');
        set_config('user_tier', '1000', 'local_customerportal');
        set_config('addon_bbb_enabled', 1, 'local_customerportal');
        set_config('addon_solr_enabled', 0, 'local_customerportal');

        $SITE->fullname = 'Portal Test Site';
        $CFG->release = '4.5.2+ (Build: 20250414)';

        $service = new sync_service($this->create_passthrough_client(), $this->create_installation_service(), 0);
        $payload = $service->build_snapshot_payload();

        $this->assertSame('00000000-0000-0000-0000-000000000123', $payload['id']);
        $this->assertSame('Portal Test Site', $payload['label']);
        $this->assertSame('4.5', $payload['moodle_version']);
        $this->assertSame('workplace', $payload['flavour']);
        $this->assertSame('newest', $payload['release_channel']);
        $this->assertSame('level_2', $payload['sla_level']);
        $this->assertSame('1000', $payload['user_tier']);
        $this->assertTrue($payload['addon_bbb_enabled']);
        $this->assertFalse($payload['addon_solr_enabled']);
    }

    /**
     * First sync should self-register once when provisioning did not pre-register yet.
     */
    public function test_sync_snapshot_registers_installation_once_as_fallback(): void {
        global $SITE, $CFG;

        set_config('installation_registered', 0, 'local_customerportal');
        $SITE->fullname = 'Portal Test Site';
        $CFG->release = '4.5.2+ (Build: 20250414)';

        $client = new class () extends api_client {
            /** @var string[] */
            public array $paths = [];

            /**
             * Constructor intentionally skips parent config loading.
             */
            public function __construct() {
            }

            /**
             * Record the target path and return an empty success payload.
             *
             * @param string $path
             * @param array $body
             * @return array
             */
            public function post(string $path, array $body): array {
                $this->paths[] = $path;
                return ['data' => []];
            }
        };

        $service = new sync_service($client, $this->create_installation_service(), 0);

        $this->assertTrue($service->sync_snapshot());
        $this->assertSame(
            ['/v1/portal/installations', '/v1/portal/installations/snapshot'],
            $client->paths
        );
        $this->assertSame('1', get_config('local_customerportal', 'installation_registered'));
    }

    /**
     * A conflict on fallback registration means the installation already exists and is safe to sync.
     */
    public function test_sync_snapshot_treats_registration_conflict_as_success(): void {
        global $SITE, $CFG;

        set_config('installation_registered', 0, 'local_customerportal');
        $SITE->fullname = 'Portal Test Site';
        $CFG->release = '4.5.2+ (Build: 20250414)';

        $client = new class () extends api_client {
            /**
             * Constructor intentionally skips parent config loading.
             */
            public function __construct() {
            }

            /**
             * Simulate a 409 on the register path and a 200 on everything else.
             *
             * @param string $path
             * @param array $body
             * @return array
             * @throws \moodle_exception
             */
            public function post(string $path, array $body): array {
                if ($path === '/v1/portal/installations') {
                    throw new \moodle_exception(
                        'error_api_unavailable',
                        'local_customerportal',
                        '',
                        null,
                        'HTTP 409: conflict'
                    );
                }

                return ['data' => []];
            }
        };

        $service = new sync_service($client, $this->create_installation_service(), 0);

        $this->assertTrue($service->sync_snapshot());
        $this->assertSame('1', get_config('local_customerportal', 'installation_registered'));
    }

    /**
     * Create a fake installation service with a fixed installation id.
     *
     * @return installation_service
     */
    private function create_installation_service(): installation_service {
        return new installation_service($this->create_passthrough_client(), '00000000-0000-0000-0000-000000000123');
    }

    /**
     * Create a no-op api client.
     *
     * @return api_client
     */
    private function create_passthrough_client(): api_client {
        return new class () extends api_client {
            /**
             * Constructor intentionally skips parent config loading.
             */
            public function __construct() {
            }

            /**
             * Return an empty success payload.
             *
             * @param string $path
             * @param array $body
             * @return array
             */
            public function post(string $path, array $body): array {
                return ['data' => []];
            }
        };
    }
}
