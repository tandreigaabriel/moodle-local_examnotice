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
 * Shared helpers for local_examnotice.
 *
 * Used by:
 *   - classes/hook/before_footer.php
 *   - admin/manage.php
 *
 * @package    local_examnotice
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the default checklist HTML used before an admin saves custom content.
 *
 * @return string
 */
function local_examnotice_default_content(): string {
    $setupurl    = get_config('local_examnotice', 'setup_url') ?: '#';
    $roomscanurl = get_config('local_examnotice', 'room_scan_url') ?: '#';
    $policyurl   = get_config('local_examnotice', 'policy_url') ?: '#';
    $qaurl       = get_config('local_examnotice', 'qa_url') ?: '#';

    return '<ul class="en-list">'
        . '<li><span class="en-icon">🔐</span><span>'
        . 'Please ensure your <strong>Windows/iOS operating systems are up to date</strong> before the exam.'
        . '</span></li>'
        . '<li><span class="en-icon">🔐</span><span>'
        . 'Complete the <strong>Exam Set Up tile</strong> before your quiz. '
        . '<a href="' . $setupurl . '" target="_blank">View Instructions</a>'
        . '</span></li>'
        . '<li><span class="en-icon">💻</span><span>'
        . 'Ensure <strong>Google Chrome</strong> is installed. '
        . '<a href="https://www.google.com/chrome/" target="_blank">Download Chrome</a>'
        . '</span></li>'
        . '<li><span class="en-icon">📷</span><span>'
        . 'A <strong>Room Scan</strong> is required. '
        . '<a href="' . $roomscanurl . '" target="_blank">View Instructions</a>'
        . '</span></li>'
        . '<li><span class="en-icon">📚</span><span>'
        . 'Review the <strong>Examination Policy</strong>. '
        . '<a href="' . $policyurl . '" target="_blank">Download Now</a>'
        . '</span></li>'
        . '<li><span class="en-icon">❓</span><span>'
        . 'For help, visit the '
        . '<a href="' . $qaurl . '" target="_blank">Exam Q&amp;A Page</a>.'
        . '</span></li>'
        . '<li><span class="en-icon">✅</span><span>'
        . 'Make sure you have completed all <strong>mini activities (mini exam)</strong> '
        . 'and ticked the <strong>Exam Setup Practice</strong> box.'
        . '</span></li>'
        . '<li><span class="en-icon">🎥</span><span>'
        . 'If your built-in webcam cannot complete the room scan, ensure an '
        . '<strong>external webcam</strong> is available.'
        . '</span></li>'
        . '<li><span class="en-icon">📞</span><span>'
        . '<strong>IT Support:</strong> Mon–Fri 09:00–17:30 | '
        . 'Sat (Exam Day) 09:00–17:30 | '
        . '📱 <strong>+441342306266</strong>'
        . '</span></li>'
        . '</ul>';
}

/**
 * Renders the exam notice modal using the local_examnotice/modal Mustache template.
 *
 * @param object $data Must have: name, days, date
 * @param string $title Modal heading text
 * @param string $content Modal body HTML
 * @return string
 */
function local_examnotice_build_modal_html(object $data, string $title, string $content): string {
    global $OUTPUT;

    return $OUTPUT->render_from_template('local_examnotice/modal', [
        'title'   => $title,
        'name'    => $data->name,
        'days'    => (int) $data->days,
        'date'    => $data->date,
        'content' => $content,
    ]);
}
