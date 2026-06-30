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
 * Library functions.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Support/contact URL for requests and invoices.
 *
 * Configurable via the "supporturl" admin setting so the portal can ship to
 * external customers; defaults to the eLeDia contact page.
 *
 * @return string
 */
function local_customerportal_support_url(): string {
    $url = (string) get_config('local_customerportal', 'supporturl');
    return $url !== '' ? $url : 'https://eledia.de/kontakt';
}

/**
 * URL of the AI suite, if available on this site.
 *
 * Configurable via the "aiurl" admin setting. Empty by default — when empty,
 * the AI navigation entries are hidden (external sites have no LernHive AI).
 *
 * @return string
 */
function local_customerportal_ai_url(): string {
    return (string) get_config('local_customerportal', 'aiurl');
}
