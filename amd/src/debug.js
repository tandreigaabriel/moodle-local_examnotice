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
 * AMD module for the exam notice debug/diagnostic page.
 *
 * @module     local_examnotice/debug
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/log'], function (Log) {

    function set_check(id, ok, msg) {
        var el = document.getElementById(id);
        if (el) {
            // FIXED: no innerHTML
            el.textContent = (ok ? 'YES' : 'NO') + (msg ? ' - ' + msg : '');
        }
    }

    function show_result(msg, ok) {
        var el = document.getElementById('testResult');
        el.style.display = 'block';
        el.className = 'alert mt-3 ' + (ok ? 'alert-success' : 'alert-danger');
        el.textContent = msg;
    }

    function capture_console() {
        var log = document.getElementById('consoleLog');
        ['log', 'warn', 'error'].forEach(function (level) {
            var orig = console[level].bind(console);
            console[level] = function () {
                orig.apply(console, arguments);
                var msg = Array.from(arguments).map(function (a) {
                    try {
                        return typeof a === 'object' ? JSON.stringify(a) : String(a);
                    } catch (e) {
                        return String(a);
                    }
                }).join(' ');
                log.textContent += '[' + level.toUpperCase() + '] ' + msg + '\n';
            };
        });
    }

    return {
        init: function () {
            capture_console();

            set_check('chk-require', typeof window.require !== 'undefined');
            set_check('chk-bootstrap', typeof window.bootstrap !== 'undefined');
            set_check('chk-modal',
                typeof window.bootstrap !== 'undefined' &&
                typeof window.bootstrap.Modal !== 'undefined'
            );

            if (typeof window.require === 'undefined') {
                set_check('chk-amd', false, 'require not available');
                show_result('window.require is not defined -- AMD loader not ready', false);
                return;
            }

            window.require(['local_examnotice/modal'], function (modal) {
                set_check('chk-amd', true, 'module loaded');
                Log.debug('local_examnotice/debug: AMD modal module loaded OK');

                document.getElementById('openTestModal').addEventListener('click', function () {
                    show_result('Modal opened -- try clicking both buttons inside it', true);
                    modal.init({
                        quizid: 0,
                        ispreview: true
                    });
                });

            }, function (err) {
                set_check('chk-amd', false, JSON.stringify(err));
                show_result('AMD module failed to load: ' + JSON.stringify(err), false);
                Log.error('local_examnotice/debug: AMD load error', err);
            });
        }
    };
});