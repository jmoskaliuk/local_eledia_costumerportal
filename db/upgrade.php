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
 * Upgrade steps for local_customerportal.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_customerportal upgrade steps.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool
 */
function xmldb_local_customerportal_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026062300) {
        $table = new xmldb_table('local_customerportal_request');

        $catalogentryfield = new xmldb_field('catalog_entry_id');
        if ($dbman->field_exists($table, $catalogentryfield)) {
            $dbman->drop_field($table, $catalogentryfield);
        }

        $directusfield = new xmldb_field('directus_id');
        if ($dbman->field_exists($table, $directusfield)) {
            $dbman->drop_field($table, $directusfield);
        }

        upgrade_plugin_savepoint(true, 2026062300, 'local', 'customerportal');
    }

    return true;
}
