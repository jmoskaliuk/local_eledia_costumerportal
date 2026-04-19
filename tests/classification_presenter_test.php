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
 * Unit tests for classification_presenter (task26 portal rendering).
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal;

use local_customerportal\local\classification_presenter;

/**
 * Tests for classification enum → template data mapping.
 *
 * @covers \local_customerportal\local\classification_presenter
 */
final class classification_presenter_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * GDPR readiness enum must map to the documented bootstrap classes.
     */
    public function test_gdpr_class_mapping(): void {
        $this->assertSame('bg-success', classification_presenter::gdpr_class('green'));
        $this->assertSame('bg-warning text-dark', classification_presenter::gdpr_class('amber'));
        $this->assertSame('bg-danger', classification_presenter::gdpr_class('red'));
        $this->assertSame('bg-secondary', classification_presenter::gdpr_class('unknown'));
        $this->assertSame('bg-secondary', classification_presenter::gdpr_class(null));
    }

    /**
     * Unknown enum values return null labels (no raw value leaks into the UI).
     */
    public function test_unknown_enum_returns_null_label(): void {
        $this->assertNull(classification_presenter::pricing_label('not_a_model'));
        $this->assertNull(classification_presenter::maintenance_label(''));
        $this->assertNull(classification_presenter::gdpr_label(null));
        $this->assertNull(classification_presenter::maintainer_type_label('vendor'));
    }

    /**
     * present_card() flags every badge needed for the card footer on the OSS example.
     */
    public function test_present_card_free_oss_plugin(): void {
        $entry = [
            'pricing_model'     => 'free',
            'maintenance_badge' => 'actively_maintained',
            'gdpr_readiness'    => 'green',
            'is_open_source'    => true,
            'is_eledia_product' => false,
            'is_deprecated'     => false,
            'maintainer'        => [
                'name'                          => 'Moodle HQ',
                'type'                          => 'organisation',
                'is_moodle_certified_partner'   => false,
            ],
        ];

        $card = classification_presenter::present_card($entry);

        $this->assertTrue($card['has_pricing_model']);
        $this->assertTrue($card['is_open_source']);
        $this->assertFalse($card['is_eledia_product']);
        $this->assertTrue($card['has_gdpr']);
        $this->assertSame('bg-success', $card['gdpr_class']);
        $this->assertFalse($card['maintainer_certified']);
        $this->assertFalse($card['is_deprecated']);
    }

    /**
     * present_card() flags certified partner + eLeDia badges on the commercial example.
     */
    public function test_present_card_commercial_eledia_product(): void {
        $entry = [
            'pricing_model'     => 'paid_subscription',
            'gdpr_readiness'    => 'green',
            'is_open_source'    => false,
            'is_eledia_product' => true,
            'maintainer'        => [
                'name'                          => 'eLeDia GmbH',
                'type'                          => 'moodle_partner',
                'is_moodle_certified_partner'   => true,
            ],
        ];

        $card = classification_presenter::present_card($entry);

        $this->assertTrue($card['is_eledia_product']);
        $this->assertTrue($card['maintainer_certified']);
        $this->assertFalse($card['is_open_source']);
    }

    /**
     * present() exposes maintainer + replacement blocks expected by the detail template.
     */
    public function test_present_detail_includes_maintainer_and_replacement(): void {
        $entry = [
            'pricing_model'         => 'free',
            'gdpr_readiness'        => 'amber',
            'is_deprecated'         => true,
            'replacement_entry_id'  => 'attendance-plus-pro',
            'last_release_version'  => '2.4.1',
            'maintainer'            => [
                'name'                          => 'Moodle HQ',
                'type'                          => 'organisation',
                'is_moodle_certified_partner'   => false,
            ],
        ];

        $detail = classification_presenter::present($entry);

        $this->assertTrue($detail['is_deprecated']);
        $this->assertTrue($detail['has_replacement']);
        $this->assertSame('attendance-plus-pro', $detail['replacement_entry_id']);
        $this->assertTrue($detail['has_maintainer']);
        $this->assertSame('Moodle HQ', $detail['maintainer_name']);
        $this->assertTrue($detail['has_last_release']);
    }

    /**
     * Entries without classification data must not create false-positive badges.
     */
    public function test_present_card_empty_entry_has_no_badges(): void {
        $card = classification_presenter::present_card([]);

        $this->assertFalse($card['has_pricing_model']);
        $this->assertFalse($card['has_maintenance']);
        $this->assertFalse($card['has_gdpr']);
        $this->assertFalse($card['is_open_source']);
        $this->assertFalse($card['is_eledia_product']);
        $this->assertFalse($card['is_deprecated']);
        $this->assertFalse($card['maintainer_certified']);
    }

    /**
     * A maintainer with missing name must not render the block.
     */
    public function test_present_detail_empty_maintainer_is_hidden(): void {
        $detail = classification_presenter::present(['maintainer' => ['type' => 'organisation']]);

        $this->assertFalse($detail['has_maintainer']);
        $this->assertFalse($detail['has_replacement']);
    }
}
