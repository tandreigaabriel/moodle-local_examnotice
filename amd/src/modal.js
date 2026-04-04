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
 * AMD module for the exam notice modal.
 *
 * @module     local_examnotice/modal
 * @package    local_examnotice
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint-env browser */
/* global define */

define(["jquery", "core/log", "core/ajax"], function ($, Log, Ajax) {

    return {
        init: function (params) {

            var el = document.getElementById("examNoticeModal");
            if (!el) {
                Log.debug("local_examnotice: modal not found");
                return;
            }

            var bsm = null;

            function show() {
                if (window.bootstrap && window.bootstrap.Modal) {
                    bsm = new window.bootstrap.Modal(el, {
                        backdrop: "static",
                        keyboard: false
                    });
                    bsm.show();
                } else if (typeof $(el).modal === "function") {
                    $(el).modal({ backdrop: "static", keyboard: false });
                    $(el).modal("show");
                } else {
                    el.style.display = "block";
                    el.classList.add("show");
                    document.body.classList.add("modal-open");
                }
            }

            function hide() {
                if (bsm) {
                    bsm.hide();
                } else if (typeof $(el).modal === "function") {
                    $(el).modal("hide");
                } else {
                    el.style.display = "none";
                    el.classList.remove("show");
                    document.body.classList.remove("modal-open");
                }
            }

            function sendAction(action) {
                return Ajax.call([{
                    methodname: "local_examnotice_dismiss_notice",
                    args: {
                        quizid: params.quizid,
                        action: action
                    }
                }])[0];
            }

            function handleConfirm() {
                if (params.ispreview) {
                    hide();
                    return;
                }

                var btn = document.getElementById("examNoticeConfirm");
                if (!btn) {
                    return;
                }

                var originalText = btn.textContent;
                btn.disabled = true;
                btn.textContent = "Saving...";

                sendAction("confirm")
                    .then(function (resp) {
                        if (resp.success) {
                            hide();
                        } else {
                            btn.disabled = false;
                            btn.textContent = originalText;
                        }
                    })
                    .catch(function () {
                        btn.disabled = false;
                        btn.textContent = originalText;
                    });
            }

            function handleDismiss() {
                if (params.ispreview) {
                    hide();
                    return;
                }

                // FIXED: 
                sendAction("dismiss")
                    .then(function () {
                        hide();
                    })
                    .catch(function () {
                        hide();
                    });
            }

            // Delegated events
            el.addEventListener("click", function (e) {
                var target = e.target.closest("#examNoticeConfirm, #examNoticeDismiss");
                if (!target) {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();

                if (target.id === "examNoticeConfirm") {
                    handleConfirm();
                } else {
                    handleDismiss();
                }
            });

            var previewBtn = document.getElementById("examNoticePreviewBtn");

            if (previewBtn) {
                previewBtn.addEventListener("click", function (e) {

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    // Delay to override Moodle drawer behaviour
                    setTimeout(function () {

                        // Force close drawer
                        document.body.classList.remove("drawer-open-right");

                        var drawer = document.querySelector('[data-region="right-hand-drawer"]');
                        if (drawer) {
                            drawer.classList.remove("show");
                        }

                        show();

                    }, 0);

                    return false;
                });
            }

            // Auto show for real usage
            if (!params.ispreview) {
                show();
            }
        }
    };
});