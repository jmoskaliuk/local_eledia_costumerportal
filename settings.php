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
        'local_customerportal/settings_heading',
        get_string('settings_heading', 'local_customerportal'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_customerportal/directus_url',
        get_string('settings_directus_url', 'local_customerportal'),
        get_string('settings_directus_url_desc', 'local_customerportal'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_customerportal/directus_token',
        get_string('settings_directus_token', 'local_customerportal'),
        get_string('settings_directus_token_desc', 'local_customerportal'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_customerportal/installation_id',
        get_string('settings_installation_id', 'local_customerportal'),
        get_string('settings_installation_id_desc', 'local_customerportal'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_customerportal/public_catalog_url',
        get_string('settings_public_catalog_url', 'local_customerportal'),
        get_string('settings_public_catalog_url_desc', 'local_customerportal'),
        '',
        PARAM_URL
    ));
}
