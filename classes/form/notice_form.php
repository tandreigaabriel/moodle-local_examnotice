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
 * Admin settings form for local_examnotice.
 *
 * @package    local_examnotice
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examnotice\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Settings form for exam notice plugin.
 */
class notice_form extends \moodleform {

    /**
     * Define form elements.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        // General settings.
        $mform->addElement('header', 'hdr_general', get_string('settings', 'local_examnotice'));

        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_examnotice'));
        $mform->setType('enabled', PARAM_BOOL);

        $mform->addElement(
            'text',
            'days_before',
            get_string('days_before', 'local_examnotice'),
            ['size' => 4]
        );
        $mform->setType('days_before', PARAM_INT);
        $mform->addRule('days_before', null, 'required', null, 'client');
        $mform->addRule('days_before', null, 'numeric', null, 'client');

        // Modal content.
        $mform->addElement('header', 'hdr_content', get_string('content_tab', 'local_examnotice'));

        $mform->addElement(
            'text',
            'modal_title',
            get_string('modal_title', 'local_examnotice'),
            ['size' => 60]
        );
        $mform->setType('modal_title', PARAM_TEXT);

        $editoroptions = [
            'maxfiles'  => 0,
            'maxbytes'  => 0,
            'trusttext' => false,
            'context'   => \context_system::instance(),
        ];

        $mform->addElement(
            'editor',
            'modal_content_editor',
            get_string('modal_content', 'local_examnotice'),
            ['rows' => 20, 'cols' => 80],
            $editoroptions
        );
        $mform->setType('modal_content_editor', PARAM_RAW);

        // Links.
        $mform->addElement('header', 'hdr_links', get_string('links_header', 'local_examnotice'));

        $urlfields = ['setup_url', 'room_scan_url', 'policy_url', 'qa_url'];
        foreach ($urlfields as $field) {
            $mform->addElement(
                'text',
                $field,
                get_string($field, 'local_examnotice'),
                ['size' => 80]
            );
            $mform->setType($field, PARAM_URL);
        }

        $this->add_action_buttons(false, get_string('savechanges', 'local_examnotice'));
    }

    /**
     * Validate form data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $days = (int) $data['days_before'];
        if ($days < 1 || $days > 90) {
            $errors['days_before'] = get_string(
                'days_before_range_error',
                'local_examnotice'
            );
        }

        return $errors;
    }
}
