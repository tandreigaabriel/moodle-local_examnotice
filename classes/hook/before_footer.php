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
 * Hook callback: inject exam notice modal before page footer.
 *
 * @package    local_examnotice
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examnotice\hook;

class before_footer {
    /**
     * Hook callback for core\hook\output\before_footer_html_generation.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     * @return void
     */
    public static function callback(
        \core\hook\output\before_footer_html_generation $hook
    ): void {
        global $PAGE, $DB, $USER, $COURSE, $CFG;

        // Load helper functions here (not at file level).
        require_once($CFG->dirroot . '/local/examnotice/lib.php');

        if (!get_config('local_examnotice', 'enabled')) {
            return;
        }

        if ($PAGE->context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        if ($COURSE->id == SITEID) {
            return;
        }

        if (!is_enrolled($PAGE->context, $USER->id, '', true)) {
            return;
        }

        if (
            has_capability('mod/quiz:grade', $PAGE->context) ||
            is_siteadmin() ||
            !has_capability('local/examnotice:view', $PAGE->context)
        ) {
            return;
        }

        $daysbefore = (int) (get_config('local_examnotice', 'days_before') ?: 7);
        $now        = time();
        $cutoff     = $now + ($daysbefore * DAYSECS);

        $sql = "SELECT q.id, q.name, q.timeopen
                  FROM {quiz} q
                  JOIN {course_modules} cm ON cm.instance = q.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
                 WHERE q.course = :courseid
                   AND q.timeopen > :now
                   AND q.timeopen <= :cutoff
                   AND cm.visible = 1
              ORDER BY q.timeopen ASC";

        $quizzes = $DB->get_records_sql($sql, [
            'courseid' => $COURSE->id,
            'now'      => $now,
            'cutoff'   => $cutoff,
        ]);

        if (empty($quizzes)) {
            return;
        }

        // Bulk-load seen records to avoid N+1 queries.
        $quizids = array_keys($quizzes);
        [$insql, $inparams] = $DB->get_in_or_equal(
            $quizids,
            SQL_PARAMS_NAMED,
            'qid'
        );
        $inparams['uid'] = $USER->id;

        $seenmap = $DB->get_records_select(
            'local_examnotice_seen',
            "userid = :uid AND quizid $insql",
            $inparams,
            '',
            'quizid, status'
        );

        foreach ($quizzes as $quiz) {
            $seen = $seenmap[$quiz->id] ?? null;

            // Status 1 = confirmed — never show again.
            if ($seen && $seen->status == 1) {
                continue;
            }

            $daysuntil = max(1, (int) ceil(($quiz->timeopen - $now) / DAYSECS));
            $examdate  = userdate(
                $quiz->timeopen,
                get_string('strftimedatefullshort', 'langconfig')
            );

            $data = (object) [
                'quizid'    => $quiz->id,
                'name'      => $quiz->name,
                'days'      => $daysuntil,
                'date'      => $examdate,
                'ispreview' => false,
            ];

            $modaltitle = get_config('local_examnotice', 'modal_title')
                ?: '🎓 Exam Preparation Instructions';

            $modalcontent = get_config('local_examnotice', 'modal_content')
                ?: local_examnotice_default_content();

            $hook->add_html(
                local_examnotice_build_modal_html($data, $modaltitle, $modalcontent)
            );

            $PAGE->requires->js_call_amd('local_examnotice/modal', 'init', [[
                'quizid'    => (int) $quiz->id,
                'ispreview' => false,
            ]]);

            return;
        }
    }
}
