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
 * Hook callback implementations.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal;

/**
 * Adds the Customer Portal entry to Moodle's primary navigation.
 */
class hook_callbacks {
    /**
     * Add Customer Portal to the primary navigation bar.
     *
     * @param \core\hook\navigation\primary_extend $hook
     */
    public static function extend_primary_navigation(
        \core\hook\navigation\primary_extend $hook
    ): void {
        $context = \core\context\system::instance();

        if (!isloggedin() || isguestuser()) {
            return;
        }

        // Get_capability_info() guards against the debug notice fired by has_capability()
        // when the plugin's capabilities are not yet registered in the DB (e.g. pending upgrade).
        if (!get_capability_info('local/customerportal:view')) {
            return;
        }

        if (!has_capability('local/customerportal:view', $context)) {
            return;
        }

        // The primary view's add() method expects the individual navigation_node
        // parameters — passing a pre-built node object lands as $text and the
        // Boost drawer template blows up on stringification.
        $hook->get_primaryview()->add(
            get_string('pluginname', 'local_customerportal'),
            new \moodle_url('/local/customerportal/index.php'),
            \navigation_node::TYPE_CUSTOM,
            null,
            'local_customerportal',
            new \pix_icon('i/dashboard', '')
        );
    }
}
