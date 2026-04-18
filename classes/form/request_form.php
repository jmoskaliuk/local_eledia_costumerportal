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
 * Request creation form.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\form;

use local_customerportal\local\request_service;

/**
 * Form for submitting a new customer request.
 */
class request_form extends \moodleform {
    /**
     * Constructor.
     *
     * @param \moodle_url $action
     * @param array $customdata Keys: catalog_entry_id (optional string).
     */
    public function __construct(\moodle_url $action, array $customdata = []) {
        parent::__construct($action->out(false), $customdata);
    }

    /**
     * Define form fields.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $typeoptions = [];
        foreach (request_service::REQUEST_TYPES as $type) {
            $typeoptions[$type] = get_string('request_' . str_replace('_request', '', $type), 'local_customerportal');
        }

        $mform->addElement(
            'select',
            'request_type',
            get_string('request_type', 'local_customerportal'),
            $typeoptions
        );
        $mform->setType('request_type', PARAM_ALPHANUMEXT);
        $mform->addRule('request_type', null, 'required');

        $mform->addElement(
            'textarea',
            'message',
            get_string('request_message', 'local_customerportal'),
            ['rows' => 6, 'cols' => 60]
        );
        $mform->setType('message', PARAM_TEXT);
        $mform->addRule('message', null, 'required');
        $mform->addHelpButton('message', 'request_message', 'local_customerportal');

        $mform->addElement('hidden', 'catalog_entry_id', $this->_customdata['catalog_entry_id'] ?? '');
        $mform->setType('catalog_entry_id', PARAM_ALPHANUMEXT);

        $this->add_action_buttons(true, get_string('request_submit', 'local_customerportal'));
    }

    /**
     * Server-side validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (!empty($data['message']) && strlen(trim($data['message'])) < 20) {
            $errors['message'] = get_string('request_message_help', 'local_customerportal');
        }

        return $errors;
    }
}
