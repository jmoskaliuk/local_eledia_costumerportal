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
 * Local AI setup guide.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();
$context = \core\context\system::instance();
if (!get_capability_info('local/customerportal:view')) {
    redirect(new \moodle_url('/'));
}
require_capability('local/customerportal:view', $context);

$PAGE->set_url('/local/customerportal/ai.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('ai_heading', 'local_customerportal'));
$PAGE->set_heading('');
$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('lh-plugin-shell-page');

$aisuiteinstalled = (bool) \core_component::get_plugin_directory('local', 'lernhive_ai');
$canconfig = has_capability('moodle/site:config', $context);

$templatedata = [
    'active_ai_setup' => true,
    'section_title' => get_string('nav_ai_setup', 'local_customerportal'),
    'url_dashboard' => (new \moodle_url('/local/customerportal/index.php'))->out(false),
    'url_installation' => (new \moodle_url('/local/customerportal/installation.php'))->out(false),
    'url_myplugins' => (new \moodle_url('/local/customerportal/myplugins.php'))->out(false),
    'url_catalog' => (new \moodle_url('/local/customerportal/catalog.php'))->out(false),
    'url_requests' => local_customerportal_support_url(),
    'url_upgrade' => (new \moodle_url('/local/customerportal/installation.php'))->out(false),
    'url_ai' => local_customerportal_ai_url(),
    'has_ai' => local_customerportal_ai_url() !== '',
    'url_ai_setup' => (new \moodle_url('/local/customerportal/ai.php'))->out(false),
    'url_invoices' => local_customerportal_support_url(),
    'url_ai_suite' => local_customerportal_ai_url(),
    'url_ai_providers' => (new \moodle_url('/admin/settings.php', ['section' => 'aiprovider']))->out(false),
    'url_ai_add_provider' => (new \moodle_url('/ai/configure.php'))->out(false),
    'url_ai_actions' => (new \moodle_url('/admin/settings.php', ['section' => 'manageaiproviders']))->out(false),
    'url_ai_search' => (new \moodle_url('/admin/search.php', ['query' => 'AI']))->out(false),
    'ai_suite_installed' => $aisuiteinstalled,
    'can_configure_ai' => $canconfig,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_customerportal/ai_setup', $templatedata);
echo $OUTPUT->footer();
