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
 * Admin settings.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_customerportal',
        get_string('pluginname', 'local_customerportal')
    );

    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading(
        'local_customerportal/settings_installation_heading',
        get_string('settings_installation_heading', 'local_customerportal'),
        get_string('settings_installation_heading_desc', 'local_customerportal')
    ));

    $settings->add(new admin_setting_configtext(
        'local_customerportal/site_label',
        get_string('settings_site_label', 'local_customerportal'),
        get_string('settings_site_label_desc', 'local_customerportal'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_customerportal/supporturl',
        get_string('settings_supporturl', 'local_customerportal'),
        get_string('settings_supporturl_desc', 'local_customerportal'),
        'https://eledia.de/kontakt',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_customerportal/aiurl',
        get_string('settings_aiurl', 'local_customerportal'),
        get_string('settings_aiurl_desc', 'local_customerportal'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configselect(
        'local_customerportal/flavour',
        get_string('settings_flavour', 'local_customerportal'),
        get_string('settings_flavour_desc', 'local_customerportal'),
        'lms',
        [
            'lms'       => get_string('settings_flavour_lms', 'local_customerportal'),
            'workplace' => get_string('settings_flavour_workplace', 'local_customerportal'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'local_customerportal/release_channel',
        get_string('settings_release_channel', 'local_customerportal'),
        get_string('settings_release_channel_desc', 'local_customerportal'),
        'lts',
        [
            'lts'    => get_string('settings_release_channel_lts', 'local_customerportal'),
            'newest' => get_string('settings_release_channel_newest', 'local_customerportal'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'local_customerportal/profile',
        get_string('settings_profile', 'local_customerportal'),
        get_string('settings_profile_desc', 'local_customerportal'),
        'managed',
        [
            'managed'     => get_string('settings_profile_managed', 'local_customerportal'),
            'self_hosted' => get_string('settings_profile_self_hosted', 'local_customerportal'),
            'demo'        => get_string('settings_profile_demo', 'local_customerportal'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'local_customerportal/sla_level',
        get_string('settings_sla_level', 'local_customerportal'),
        get_string('settings_sla_level_desc', 'local_customerportal'),
        'level_1',
        [
            'level_1' => get_string('settings_sla_level_1', 'local_customerportal'),
            'level_2' => get_string('settings_sla_level_2', 'local_customerportal'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'local_customerportal/user_tier',
        get_string('settings_user_tier', 'local_customerportal'),
        get_string('settings_user_tier_desc', 'local_customerportal'),
        '250',
        [
            '250'  => '250',
            '500'  => '500',
            '1000' => '1000',
            '2000' => '2000',
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_customerportal/addon_bbb_enabled',
        get_string('settings_addon_bbb_enabled', 'local_customerportal'),
        get_string('settings_addon_bbb_enabled_desc', 'local_customerportal'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_customerportal/addon_solr_enabled',
        get_string('settings_addon_solr_enabled', 'local_customerportal'),
        get_string('settings_addon_solr_enabled_desc', 'local_customerportal'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_customerportal/storage_quota_gb',
        get_string('settings_storage_quota_gb', 'local_customerportal'),
        get_string('settings_storage_quota_gb_desc', 'local_customerportal'),
        '',
        PARAM_FLOAT
    ));
}
