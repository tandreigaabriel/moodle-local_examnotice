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
 * Admin settings and live preview page for local_examnotice.
 *
 * @package    local_examnotice
 * @copyright  2026 local_examnotice contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/examnotice/lib.php');

require_login();
require_capability('local/examnotice:manage', context_system::instance());

$tab = optional_param('tab', 'settings', PARAM_ALPHA);

admin_externalpage_setup('local_examnotice_manage');

$PAGE->set_url(new moodle_url('/local/examnotice/admin/manage.php'));
$PAGE->set_title(get_string('admin_page_heading', 'local_examnotice'));
$PAGE->set_heading(get_string('admin_page_heading', 'local_examnotice'));

if ($tab === 'preview') {
    $PAGE->requires->js_call_amd('local_examnotice/modal', 'init', [[
        'quizid'    => 0,
        'ispreview' => true,
    ]]);
}

$config = (object)[
    'enabled'              => (int)get_config('local_examnotice', 'enabled'),
    'days_before'          => (int)(get_config('local_examnotice', 'days_before') ?: 7),
    'modal_title'          => get_config('local_examnotice', 'modal_title')
        ?: '🎓 Exam Preparation Instructions',
    'modal_content_editor' => [
        'text'   => get_config('local_examnotice', 'modal_content')
            ?: local_examnotice_default_content(),
        'format' => FORMAT_HTML,
    ],
    'setup_url'     => get_config('local_examnotice', 'setup_url')     ?: '#',
    'room_scan_url' => get_config('local_examnotice', 'room_scan_url') ?: '#',
    'policy_url'    => get_config('local_examnotice', 'policy_url')    ?: '#',
    'qa_url'        => get_config('local_examnotice', 'qa_url')        ?: '#',
];

require_once($CFG->dirroot . '/local/examnotice/classes/form/notice_form.php');
$form = new \local_examnotice\form\notice_form();
$form->set_data($config);

if ($data = $form->get_data()) {
    set_config('enabled',       (int)$data->enabled,                         'local_examnotice');
    set_config('days_before',   (int)$data->days_before,                     'local_examnotice');
    set_config('modal_title',   clean_param($data->modal_title, PARAM_TEXT), 'local_examnotice');
    set_config('modal_content', $data->modal_content_editor['text'],          'local_examnotice');
    set_config('setup_url',     $data->setup_url,                             'local_examnotice');
    set_config('room_scan_url', $data->room_scan_url,                         'local_examnotice');
    set_config('policy_url',    $data->policy_url,                            'local_examnotice');
    set_config('qa_url',        $data->qa_url,                                'local_examnotice');

    \core\notification::success(get_string('changessaved'));
    redirect($PAGE->url);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('admin_page_heading', 'local_examnotice'));

$tabs = [
    new tabobject(
        'settings',
        new moodle_url($PAGE->url, ['tab' => 'settings']),
        get_string('content_tab', 'local_examnotice')
    ),
    new tabobject(
        'preview',
        new moodle_url($PAGE->url, ['tab' => 'preview']),
        get_string('preview_tab', 'local_examnotice')
    ),
];
echo $OUTPUT->tabtree($tabs, $tab);

if ($tab === 'preview') {

    echo html_writer::tag('p',
        get_string('preview_note', 'local_examnotice'),
        ['class' => 'alert alert-info']
    );

    echo html_writer::div(
        html_writer::tag('button',
            get_string('open_preview', 'local_examnotice'),
            ['class' => 'btn btn-primary btn-lg', 'id' => 'examNoticePreviewBtn']
        ),
        'mb-4'
    );

    $previewdata = (object)[
        'quizid'    => 0,
        'name'      => 'Sample Anatomy Exam',
        'days'      => 5,
        'date'      => userdate(
            time() + 5 * DAYSECS,
            get_string('strftimedatefullshort', 'langconfig')
        ),
        'ispreview' => true,
    ];

    $modaltitle   = get_config('local_examnotice', 'modal_title')
        ?: '🎓 Exam Preparation Instructions';
    $modalcontent = get_config('local_examnotice', 'modal_content')
        ?: local_examnotice_default_content();

    echo local_examnotice_build_modal_html($previewdata, $modaltitle, $modalcontent);

} else {
    $form->display();
}

echo $OUTPUT->footer();