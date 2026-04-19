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
 * Presenter for task25 classification fields — maps raw API payload
 * to template-ready data for both list cards and detail blocks.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\local;

/**
 * Translates classification enums and boolean flags into template data.
 *
 * Enum values come straight from the /v1/catalog/* contract (task25).
 */
class classification_presenter {
    /**
     * Pricing model enum → lang-string key.
     *
     * @param string|null $value Raw enum value from API.
     * @return string|null Translated label, or null when value is unknown/empty.
     */
    public static function pricing_label(?string $value): ?string {
        $map = [
            'free'                => 'pricing_model_free',
            'freemium'            => 'pricing_model_freemium',
            'paid_onetime'        => 'pricing_model_paid_onetime',
            'paid_subscription'   => 'pricing_model_paid_subscription',
            'paid_support_only'   => 'pricing_model_paid_support_only',
        ];
        return isset($map[$value]) ? get_string($map[$value], 'local_customerportal') : null;
    }

    /**
     * Maintenance badge enum → lang-string key.
     *
     * @param string|null $value
     * @return string|null
     */
    public static function maintenance_label(?string $value): ?string {
        $map = [
            'actively_maintained'     => 'maintenance_actively_maintained',
            'looking_for_maintainer'  => 'maintenance_looking_for_maintainer',
            'orphaned'                => 'maintenance_orphaned',
            'unknown'                 => 'maintenance_unknown',
        ];
        return isset($map[$value]) ? get_string($map[$value], 'local_customerportal') : null;
    }

    /**
     * GDPR readiness enum → lang-string key.
     *
     * @param string|null $value
     * @return string|null
     */
    public static function gdpr_label(?string $value): ?string {
        $map = [
            'green'   => 'gdpr_readiness_green',
            'amber'   => 'gdpr_readiness_amber',
            'red'     => 'gdpr_readiness_red',
            'unknown' => 'gdpr_readiness_unknown',
        ];
        return isset($map[$value]) ? get_string($map[$value], 'local_customerportal') : null;
    }

    /**
     * GDPR readiness enum → Bootstrap badge class.
     *
     * @param string|null $value
     * @return string
     */
    public static function gdpr_class(?string $value): string {
        return match ($value) {
            'green'   => 'bg-success',
            'amber'   => 'bg-warning text-dark',
            'red'     => 'bg-danger',
            default   => 'bg-secondary',
        };
    }

    /**
     * Maintainer type enum → lang-string key.
     *
     * @param string|null $value
     * @return string|null
     */
    public static function maintainer_type_label(?string $value): ?string {
        $map = [
            'individual'     => 'plugin_maintainer_type_individual',
            'organisation'   => 'plugin_maintainer_type_organisation',
            'moodle_partner' => 'plugin_maintainer_type_moodle_partner',
            'eledia'         => 'plugin_maintainer_type_eledia',
        ];
        return isset($map[$value]) ? get_string($map[$value], 'local_customerportal') : null;
    }

    /**
     * Card-ready classification data for one catalog entry.
     *
     * @param array $entry Raw entry from /v1/catalog/search or /v1/catalog/{slug}.
     * @return array
     */
    public static function present_card(array $entry): array {
        $pricing = $entry['pricing_model'] ?? null;
        $maintbadge = $entry['maintenance_badge'] ?? null;
        $gdpr = $entry['gdpr_readiness'] ?? null;
        $maintainer = $entry['maintainer'] ?? null;

        return [
            'pricing_model_label'   => self::pricing_label($pricing),
            'has_pricing_model'     => self::pricing_label($pricing) !== null,
            'maintenance_label'     => self::maintenance_label($maintbadge),
            'has_maintenance'       => self::maintenance_label($maintbadge) !== null,
            'gdpr_label'            => self::gdpr_label($gdpr),
            'gdpr_class'            => self::gdpr_class($gdpr),
            'has_gdpr'              => self::gdpr_label($gdpr) !== null,
            'is_open_source'        => !empty($entry['is_open_source']),
            'is_eledia_product'     => !empty($entry['is_eledia_product']),
            'is_deprecated'         => !empty($entry['is_deprecated']),
            'maintainer_certified'  => is_array($maintainer) && !empty($maintainer['is_moodle_certified_partner']),
        ];
    }

    /**
     * Detail-page classification + maintainer block data.
     *
     * @param array $entry Raw entry from /v1/catalog/{slug}.
     * @return array
     */
    public static function present(array $entry): array {
        $maintainer = is_array($entry['maintainer'] ?? null) ? $entry['maintainer'] : null;
        $maintainertype = $maintainer['type'] ?? null;
        $replacementid = $entry['replacement_entry_id'] ?? null;

        return array_merge(self::present_card($entry), [
            'last_release_version'  => $entry['last_release_version'] ?? null,
            'has_last_release'      => !empty($entry['last_release_version']),
            'replacement_entry_id'  => $replacementid,
            'has_replacement'       => $replacementid !== null && $replacementid !== '',
            'has_maintainer'        => $maintainer !== null && !empty($maintainer['name']),
            'maintainer_name'       => $maintainer['name'] ?? null,
            'maintainer_type_label' => self::maintainer_type_label($maintainertype),
        ]);
    }
}
