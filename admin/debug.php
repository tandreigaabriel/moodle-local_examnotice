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
 * Debug/diagnostic page for local_examnotice (remove before production deployment).
 *
 * @package    local_examnotice
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/examnotice:manage', context_system::instance());

admin_externalpage_setup('local_examnotice_manage');

$PAGE->set_url(new moodle_url('/local/examnotice/admin/debug.php'));
$PAGE->set_title(get_string('debug_title', 'local_examnotice'));
$PAGE->set_heading(get_string('debug_title', 'local_examnotice'));

$PAGE->requires->js_call_amd('local_examnotice/debug', 'init');

echo $OUTPUT->header();
?>

<div class="card m-4">
  <div class="card-header bg-dark text-white"><strong>🔍 <?php echo get_string('debug_title', 'local_examnotice'); ?></strong></div>
  <div class="card-body">

    <h5>1. PHP Config Checks</h5>
    <table class="table table-sm table-bordered">
      <tr>
        <td>Plugin enabled</td>
        <td><code><?php echo var_export((bool)get_config('local_examnotice', 'enabled'), true); ?></code></td>
      </tr>
      <tr>
        <td>days_before</td>
        <td><code><?php echo get_config('local_examnotice', 'days_before')
            ?: get_string('debug_notset_daysdefault', 'local_examnotice'); ?></code></td>
      </tr>
      <tr>
        <td>modal_title</td>
        <td><code><?php echo s(get_config('local_examnotice', 'modal_title')
            ?: get_string('debug_notset', 'local_examnotice')); ?></code></td>
      </tr>
      <tr>
        <td>lib.php exists</td>
        <td><code><?php echo file_exists($CFG->dirroot . '/local/examnotice/lib.php') ? '✅ YES' : '❌ NO'; ?></code></td>
      </tr>
      <tr>
        <td>amd/src/modal.js exists</td>
        <td><code><?php echo file_exists($CFG->dirroot . '/local/examnotice/amd/src/modal.js') ? '✅ YES' : '❌ NO'; ?></code></td>
      </tr>
      <tr>
        <td>amd/build/modal.min.js exists</td>
        <td><code><?php echo file_exists($CFG->dirroot . '/local/examnotice/amd/build/modal.min.js') ? '✅ YES' : '❌ NO'; ?></code></td>
      </tr>
      <tr>
        <td>$CFG->cachejs</td>
        <td><code><?php echo isset($CFG->cachejs) ? var_export($CFG->cachejs, true)
            : get_string('debug_notset', 'local_examnotice'); ?></code></td>
      </tr>
      <tr>
        <td>Moodle version</td>
        <td><code><?php echo $CFG->version; ?></code></td>
      </tr>
      <tr>
        <td>AMD build file URL</td>
        <td>
          <a href="<?php echo $CFG->wwwroot; ?>/local/examnotice/amd/build/modal.min.js" target="_blank">
            Click to open — should show JS, not 404
          </a>
        </td>
      </tr>
    </table>

    <h5 class="mt-4">2. JavaScript Runtime Checks</h5>
    <table class="table table-sm table-bordered">
      <tr><td>window.require defined</td><td id="chk-require">⏳</td></tr>
      <tr><td>window.bootstrap defined</td><td id="chk-bootstrap">⏳</td></tr>
      <tr><td>window.bootstrap.Modal defined</td><td id="chk-modal">⏳</td></tr>
      <tr><td>AMD local_examnotice/modal loads</td><td id="chk-amd">⏳</td></tr>
    </table>

    <h5 class="mt-4">3. Modal Open/Close Test</h5>
    <p class="text-muted">Click the button below. The modal should open, both buttons should work, and closing it should leave the page fully interactive.</p>

    <button class="btn btn-primary" id="openTestModal">Open Test Modal</button>
    <div id="testResult" class="alert alert-secondary mt-3" style="display:none;"></div>

    <h5 class="mt-4">4. Console Output</h5>
    <pre id="consoleLog" class="bg-dark text-success p-3" style="min-height:80px;font-size:12px;">(waiting…)</pre>

  </div>
</div>

<!-- Test modal — NOT auto-opened, triggered manually by button only -->
<div class="modal fade" id="examNoticeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Test Modal</h5>
      </div>
      <div class="modal-body">
        If you can click the buttons below and the modal closes properly, everything is working.
      </div>
      <div class="modal-footer">
        <button id="examNoticeConfirm" class="btn btn-success">I have read and understood</button>
        <button id="examNoticeDismiss" class="btn btn-secondary">Remind me later</button>
      </div>
    </div>
  </div>
</div>

<?php
echo $OUTPUT->footer();
