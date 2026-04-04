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
 * English language strings for local_examnotice.
 *
 * @package    local_examnotice
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin.
$string['pluginname']           = 'Exam Notice';

// Navigation / UI.
$string['manage']               = 'Manage Exam Notice';
$string['settings']             = 'Exam Notice settings';
$string['preview']              = 'Preview modal';
$string['savechanges']          = 'Save changes';

// Settings.
$string['enabled']              = 'Enable plugin';
$string['enabled_desc']         = 'Show the exam preparation modal to students.';
$string['days_before']          = 'Days before exam';
$string['days_before_desc']     = 'Number of days before a quiz opens to display the notice.';
$string['days_before_range_error'] = 'Days before exam must be between 1 and 90.';
$string['modal_title']          = 'Modal title';
$string['modal_title_desc']     = 'Heading displayed at the top of the modal.';
$string['modal_content']        = 'Modal body content';
$string['modal_content_desc']   = 'HTML content displayed inside the modal.';
$string['setup_url']            = 'Exam setup instructions URL';
$string['room_scan_url']        = 'Room scan instructions URL';
$string['policy_url']           = 'Examination policy URL';
$string['qa_url']               = 'Exam Q&A page URL';
$string['links_header']         = 'Links';

// Admin page.
$string['admin_page_heading']   = 'Exam Notice — Settings & Preview';
$string['debug_notset']              = '(not set)';
$string['debug_notset_daysdefault']  = '(not set, default 7)';
$string['debug_title']          = 'Exam Notice debug';
$string['content_tab']          = 'Content & links';
$string['preview_tab']          = 'Live preview';
$string['preview_note']         = 'This is how the modal will appear to students.';
$string['open_preview']         = 'Open preview modal';

// AJAX / External Service error strings.
$string['error_quiznotfound']   = 'Quiz not found.';

// Modal button labels.
$string['remind_later']         = 'Remind me later';
$string['confirm_read']         = 'I have read and understood';

// Modal badge text.
$string['examnotice_badge_prefix']   = 'Your exam';
$string['examnotice_badge_opens_in'] = 'opens in';
$string['examnotice_badge_days']     = 'day(s)';

// Capabilities.
$string['examnotice:manage']    = 'Manage exam notice settings';
$string['examnotice:view']      = 'View exam notice modal';

// Privacy API.
$string['privacy:metadata:local_examnotice_seen']              = 'Stores whether a student has confirmed or dismissed an exam preparation notice for a given quiz.';
$string['privacy:metadata:local_examnotice_seen:userid']       = 'The ID of the student who interacted with the notice.';
$string['privacy:metadata:local_examnotice_seen:quizid']       = 'The ID of the quiz the notice relates to.';
$string['privacy:metadata:local_examnotice_seen:courseid']     = 'The ID of the course the quiz belongs to.';
$string['privacy:metadata:local_examnotice_seen:status']       = 'Interaction status: 0 = dismissed (remind later), 1 = confirmed (read and understood).';
$string['privacy:metadata:local_examnotice_seen:timecreated']  = 'Timestamp of the first interaction.';
$string['privacy:metadata:local_examnotice_seen:timemodified'] = 'Timestamp of the last update.';