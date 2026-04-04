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
 * Privacy provider for local_examnotice.
 *
 * @package    local_examnotice
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examnotice\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Declare the personal data stored in local_examnotice_seen.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_examnotice_seen',
            [
                'userid'       => 'privacy:metadata:local_examnotice_seen:userid',
                'quizid'       => 'privacy:metadata:local_examnotice_seen:quizid',
                'courseid'     => 'privacy:metadata:local_examnotice_seen:courseid',
                'status'       => 'privacy:metadata:local_examnotice_seen:status',
                'timecreated'  => 'privacy:metadata:local_examnotice_seen:timecreated',
                'timemodified' => 'privacy:metadata:local_examnotice_seen:timemodified',
            ],
            'privacy:metadata:local_examnotice_seen'
        );
        return $collection;
    }

    /**
     * Get the list of course contexts that contain personal data for a user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_examnotice_seen} s ON s.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel
                   AND s.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid'       => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a given context.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        $sql = "SELECT userid FROM {local_examnotice_seen} WHERE courseid = :courseid";
        $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);
    }

    /**
     * Export personal data for a user within the given contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $records = $DB->get_records('local_examnotice_seen', [
                'userid'   => $userid,
                'courseid' => $context->instanceid,
            ]);
            if ($records) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_examnotice')],
                    (object)['notices' => array_values($records)]
                );
            }
        }
    }

    /**
     * Delete all personal data for all users in the given context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        $DB->delete_records('local_examnotice_seen', ['courseid' => $context->instanceid]);
    }

    /**
     * Delete personal data for a specific user across the given contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $DB->delete_records('local_examnotice_seen', [
                'userid'   => $userid,
                'courseid' => $context->instanceid,
            ]);
        }
    }

    /**
     * Delete personal data for a list of users within a context.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['courseid'] = $context->instanceid;
        $DB->delete_records_select(
            'local_examnotice_seen',
            "courseid = :courseid AND userid $insql",
            $params
        );
    }
}
