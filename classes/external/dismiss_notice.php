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
 * External function: record confirm or dismiss action for an exam notice.
 *
 * @package    local_examnotice
 * @copyright  2026 local_examnotice contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examnotice\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

/**
 * External function to dismiss or confirm an exam preparation notice.
 */
class dismiss_notice extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT,   'The ID of the quiz'),
            'action' => new external_value(PARAM_ALPHA, 'Action: confirm or dismiss'),
        ]);
    }

    /**
     * Record the student's interaction with the exam notice modal.
     *
     * @param  int    $quizid
     * @param  string $action  'confirm' or 'dismiss'
     * @return array
     */
    public static function execute(int $quizid, string $action): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'quizid' => $quizid,
            'action' => $action,
        ]);

        if (!in_array($params['action'], ['confirm', 'dismiss'])) {
            throw new \invalid_parameter_exception(get_string('error'));
        }

        $quiz    = $DB->get_record('quiz', ['id' => $params['quizid']], 'id, course', MUST_EXIST);
        $context = \context_course::instance($quiz->course);

        // Validates session and context, throws if invalid.
        self::validate_context($context);
        require_capability('local/examnotice:view', $context);

        $now    = time();
        $status = ($params['action'] === 'confirm') ? 1 : 0;

        $existing = $DB->get_record('local_examnotice_seen', [
            'userid' => $USER->id,
            'quizid' => $params['quizid'],
        ]);

        if ($existing) {
            // Status only ever increases (dismiss -> confirm, never the reverse).
            if ($status > $existing->status) {
                $existing->status       = $status;
                $existing->timemodified = $now;
                $DB->update_record('local_examnotice_seen', $existing);
            }
        } else {
            $DB->insert_record('local_examnotice_seen', (object)[
                'userid'       => $USER->id,
                'quizid'       => $params['quizid'],
                'courseid'     => $quiz->course,
                'status'       => $status,
                'timecreated'  => $now,
                'timemodified' => $now,
            ]);
        }

        return ['success' => true];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation succeeded'),
        ]);
    }
}
