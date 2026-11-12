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
 * Security and privacy regression tests.
 *
 * @package    local_examnotice
 * @copyright  2026 Andrei Toma
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examnotice;

use local_examnotice\external\dismiss_notice;
use local_examnotice\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;

/**
 * Exercises rendering, activity authorisation and privacy isolation.
 *
 * @covers \local_examnotice\external\dismiss_notice
 * @covers \local_examnotice\hook\before_footer
 * @covers \local_examnotice\privacy\provider
 * @covers ::local_examnotice_build_modal_html
 */
final class security_test extends \core_privacy\tests\provider_testcase {
    /**
     * Create a synthetic enrolled student and quiz.
     *
     * @return array Course, user and quiz.
     */
    private function fixture(): array {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => time() + DAYSECS,
        ]);
        $this->setUser($user);
        return [$course, $user, $quiz];
    }

    /**
     * Test rich text cleaning without losing allowed formatting.
     */
    public function test_rendering_cleans_untrusted_html(): void {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/local/examnotice/lib.php');
        [$course] = $this->fixture();
        $PAGE->set_context(\context_course::instance($course->id));
        $html = local_examnotice_build_modal_html(
            (object) [
                'name' => '<img src=x onerror="alert(2)">Épreuve 🧪', 'days' => 1, 'date' => 'Tomorrow',
            ],
            '<script>alert(3)</script>',
            '<p><strong>Read this</strong></p><script>alert(1)</script><img src=x onerror="alert(4)">'
        );
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror=', html_entity_decode(strip_tags($html, '<img>')));
        $this->assertStringContainsString('<strong>Read this</strong>', $html);
        $this->assertStringContainsString('Épreuve 🧪', $html);
    }

    /**
     * Test attribute escaping in generated default links.
     */
    public function test_default_links_escape_attributes(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/examnotice/lib.php');
        $this->resetAfterTest();
        set_config('setup_url', 'https://example.invalid/" onclick="alert(1)', 'local_examnotice');
        $this->assertStringNotContainsString('" onclick="', local_examnotice_default_content());
    }

    /**
     * Test valid updates never downgrade confirmation.
     */
    public function test_confirmation_is_monotonic(): void {
        global $DB;
        [, $user, $quiz] = $this->fixture();
        $this->assertTrue(dismiss_notice::execute($quiz->id, 'dismiss')['success']);
        dismiss_notice::execute($quiz->id, 'confirm');
        dismiss_notice::execute($quiz->id, 'dismiss');
        $this->assertEquals(1, $DB->get_field('local_examnotice_seen', 'status', [
            'userid' => $user->id, 'quizid' => $quiz->id,
        ], MUST_EXIST));
        $this->assertEquals(1, $DB->count_records('local_examnotice_seen'));
    }

    /**
     * Test an outsider with the capability still cannot write.
     */
    public function test_unenrolled_user_cannot_write(): void {
        global $DB;
        [$course, , $quiz] = $this->fixture();
        $outsider = $this->getDataGenerator()->create_user();
        $role = $this->getDataGenerator()->create_role();
        assign_capability('local/examnotice:view', CAP_ALLOW, $role, \context_course::instance($course->id));
        role_assign($role, $outsider->id, \context_course::instance($course->id));
        $this->setUser($outsider);
        try {
            dismiss_notice::execute($quiz->id, 'confirm');
            $this->fail('An unenrolled user must be rejected.');
        } catch (\moodle_exception $e) {
            $this->assertEquals(0, $DB->count_records('local_examnotice_seen'));
        }
    }

    /**
     * Test suspended enrolments cannot write even with an assigned role.
     */
    public function test_suspended_enrolment_cannot_write(): void {
        global $DB;
        [, $user, $quiz] = $this->fixture();
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, ['userid' => $user->id]);
        try {
            dismiss_notice::execute($quiz->id, 'confirm');
            $this->fail('A suspended enrolment must be rejected.');
        } catch (\moodle_exception $e) {
            $this->assertEquals(0, $DB->count_records('local_examnotice_seen'));
        }
    }

    /**
     * Test unknown actions are rejected before writing.
     */
    public function test_invalid_action_cannot_write(): void {
        global $DB;
        [, , $quiz] = $this->fixture();
        try {
            dismiss_notice::execute($quiz->id, 'delete');
            $this->fail('An unknown action must be rejected.');
        } catch (\invalid_parameter_exception $e) {
            $this->assertEquals(0, $DB->count_records('local_examnotice_seen'));
        }
    }

    /**
     * Test hidden quizzes reject writes.
     */
    public function test_hidden_quiz_cannot_be_confirmed(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        [, , $quiz] = $this->fixture();
        set_coursemodule_visible($quiz->cmid, 0);
        try {
            dismiss_notice::execute($quiz->id, 'confirm');
            $this->fail('A hidden quiz must be rejected.');
        } catch (\moodle_exception $e) {
            $this->assertEquals(0, $DB->count_records('local_examnotice_seen'));
        }
    }

    /**
     * Test a visible quiz with an unmet availability restriction.
     */
    public function test_restricted_quiz_is_not_exposed(): void {
        global $DB, $PAGE, $COURSE, $OUTPUT;
        [$course, , $quiz] = $this->fixture();
        set_config('enableavailability', 1);
        $DB->set_field('course_modules', 'availability', json_encode([
            'op' => '&', 'c' => [['type' => 'date', 'd' => '>=', 't' => time() + WEEKSECS]], 'showc' => [true],
        ]), ['id' => $quiz->cmid]);
        rebuild_course_cache($course->id, true);
        $COURSE = $course;
        $PAGE->set_course($course);
        $PAGE->set_context(\context_course::instance($course->id));
        set_config('enabled', 1, 'local_examnotice');
        $hook = new \core\hook\output\before_footer_html_generation($OUTPUT);
        \local_examnotice\hook\before_footer::callback($hook);
        $this->assertSame('', $hook->get_output());
        try {
            dismiss_notice::execute($quiz->id, 'confirm');
            $this->fail('An unavailable quiz must be rejected.');
        } catch (\moodle_exception $e) {
            $this->assertEquals(0, $DB->count_records('local_examnotice_seen'));
        }
    }

    /**
     * Test export and deletion remain scoped to the approved user/course.
     */
    public function test_privacy_export_and_deletion_are_scoped(): void {
        global $DB;
        [$course, $user, $quiz] = $this->fixture();
        dismiss_notice::execute($quiz->id, 'confirm');
        $other = $this->getDataGenerator()->create_user();
        $othercourse = $this->getDataGenerator()->create_course();
        $record = $DB->get_record('local_examnotice_seen', ['userid' => $user->id], '*', MUST_EXIST);
        unset($record->id);
        $record->userid = $other->id;
        $DB->insert_record('local_examnotice_seen', $record);
        $record->userid = $user->id;
        $record->courseid = $othercourse->id;
        $record->quizid = $quiz->id + 1;
        $DB->insert_record('local_examnotice_seen', $record);
        $context = \context_course::instance($course->id);
        $approved = new approved_contextlist($user, 'local_examnotice', [$context->id]);
        provider::export_user_data($approved);
        $export = \core_privacy\local\request\writer::with_context($context)->get_data([
            get_string('pluginname', 'local_examnotice'),
        ]);
        $this->assertCount(1, $export->notices);
        $this->assertEquals($user->id, $export->notices[0]->userid);
        $this->assertContains($context->id, provider::get_contexts_for_userid($user->id)->get_contextids());
        provider::delete_data_for_users(new approved_userlist($context, 'local_examnotice', []));
        $this->assertEquals(3, $DB->count_records('local_examnotice_seen'));
        provider::delete_data_for_user($approved);
        $this->assertEquals(2, $DB->count_records('local_examnotice_seen'));
        provider::delete_data_for_users(new approved_userlist($context, 'local_examnotice', [$other->id]));
        $this->assertEquals(1, $DB->count_records('local_examnotice_seen'));
        provider::delete_data_for_all_users_in_context(\context_course::instance($othercourse->id));
        $this->assertEquals(0, $DB->count_records('local_examnotice_seen'));
    }
}
