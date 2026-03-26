import jQuery from "jquery";
import * as bootstrap from "bootstrap";
import { Chart, registerables } from "chart.js";
import "./site-stats-datatables.js";
import "./site-filters.js";
import "./site-export.js";
import "./preferences-settings.js";

window.$ = window.jQuery = jQuery;
window.bootstrap = bootstrap;
Chart.register(...registerables);
window.Chart = Chart;

(function () {
    "use strict";

    jQuery(function () {
        function isMobileSidebar() {
            return window.matchMedia("(max-width: 767.98px)").matches;
        }

        function closeMobileSidebar() {
            jQuery("body").removeClass("sidebar-mobile-open");
            const bd = document.getElementById("sidebarBackdrop");
            if (bd) {
                bd.setAttribute("aria-hidden", "true");
            }
        }

        function openMobileSidebar() {
            jQuery("body").addClass("sidebar-mobile-open");
            const bd = document.getElementById("sidebarBackdrop");
            if (bd) {
                bd.setAttribute("aria-hidden", "false");
            }
        }

        jQuery("#sidebarToggle").on("click", function (e) {
            e.preventDefault();
            jQuery("body").toggleClass("sidebar-toggled");
            jQuery(".sidebar").toggleClass("toggled");
        });

        jQuery("#sidebarToggleTop").on("click", function (e) {
            e.preventDefault();
            if (jQuery("body").hasClass("sidebar-mobile-open")) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        });

        jQuery("#sidebarBackdrop").on("click", function () {
            closeMobileSidebar();
        });

        jQuery("#accordionSidebar").on("click", "a[href]", function () {
            if (isMobileSidebar()) {
                closeMobileSidebar();
            }
        });

        jQuery("#accordionSidebar").on(
            "click",
            'form button[type="submit"]',
            function () {
                if (isMobileSidebar()) {
                    closeMobileSidebar();
                }
            },
        );

        jQuery(document).on("keydown", function (e) {
            if (
                e.key === "Escape" &&
                jQuery("body").hasClass("sidebar-mobile-open")
            ) {
                closeMobileSidebar();
            }
        });

        jQuery(window).on("resize", function () {
            if (jQuery(window).width() < 768) {
                jQuery("body").removeClass("sidebar-toggled");
                jQuery(".sidebar").removeClass("toggled");
            } else {
                closeMobileSidebar();
            }
        });

        jQuery(document).on("scroll", function () {
            const st = jQuery(this).scrollTop();
            if (st > 100) {
                jQuery(".scroll-to-top").fadeIn();
            } else {
                jQuery(".scroll-to-top").fadeOut();
            }
        });

        jQuery(".scroll-to-top").on("click", function (e) {
            e.preventDefault();
            jQuery("html, body").animate({ scrollTop: 0 }, 500);
        });
    });

    document.querySelectorAll("[data-copy]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            const text = btn.getAttribute("data-copy");
            if (!text || !navigator.clipboard) return;
            navigator.clipboard.writeText(text).then(function () {
                const orig = btn.innerHTML;
                const done = btn.getAttribute("data-copy-done") || "Copiato";
                btn.innerHTML = '<i class="fas fa-check me-1"></i>' + done;
                setTimeout(function () {
                    btn.innerHTML = orig;
                }, 2000);
            });
        });
    });

    const qr = document.getElementById("pa-two-factor-qr");
    if (qr && qr.dataset.qrUrl) {
        fetch(qr.dataset.qrUrl, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (data && data.svg) qr.innerHTML = data.svg;
            })
            .catch(function () {});
    }

    (function initTwoFactorRecovery() {
        const wrap = document.getElementById("pa-recovery-codes-wrap");
        if (!wrap || !wrap.dataset.codesUrl) {
            return;
        }

        const userId = wrap.dataset.userId || "0";
        const storageKey = "indiestats_2fa_recovery_ack_" + userId;

        const codesEl = document.getElementById("pa-recovery-codes");
        const hiddenHint = document.getElementById(
            "pa-recovery-codes-hidden-hint",
        );
        const actionsEl = document.getElementById("pa-recovery-codes-actions");
        const dismissBtn = document.getElementById("pa-recovery-codes-dismiss");
        const copyBtn = document.getElementById("pa-recovery-codes-copy");
        const regenForm = document.getElementById(
            "pa-regenerate-recovery-form",
        );

        if (!codesEl || !hiddenHint || !actionsEl || !dismissBtn) {
            return;
        }

        let lastCodes = [];

        function escapeHtml(s) {
            return String(s)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;");
        }

        function showHiddenState() {
            codesEl.classList.add("d-none");
            codesEl.innerHTML = "";
            actionsEl.classList.add("d-none");
            hiddenHint.classList.remove("d-none");
            lastCodes = [];
        }

        function showCodesState(codes) {
            lastCodes = codes.slice();
            codesEl.innerHTML = codes
                .map(function (c) {
                    return (
                        '<code class="d-block mb-1 user-select-all">' +
                        escapeHtml(c) +
                        "</code>"
                    );
                })
                .join("");
            codesEl.classList.remove("d-none");
            hiddenHint.classList.add("d-none");
            actionsEl.classList.remove("d-none");
        }

        if (localStorage.getItem(storageKey) === "1") {
            showHiddenState();
        } else {
            fetch(wrap.dataset.codesUrl, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
            })
                .then(function (r) {
                    return r.json();
                })
                .then(function (codes) {
                    if (!Array.isArray(codes) || !codes.length) {
                        return;
                    }
                    showCodesState(codes);
                })
                .catch(function () {});
        }

        dismissBtn.addEventListener("click", function () {
            localStorage.setItem(storageKey, "1");
            showHiddenState();
        });

        if (copyBtn && navigator.clipboard) {
            copyBtn.addEventListener("click", function () {
                if (!lastCodes.length) {
                    return;
                }
                const labelCopied = wrap.dataset.labelCopied || "Copiato";
                const labelCopy = wrap.dataset.labelCopy || "Copia tutti";
                navigator.clipboard
                    .writeText(lastCodes.join("\n"))
                    .then(function () {
                        const orig = copyBtn.innerHTML;
                        copyBtn.innerHTML =
                            '<i class="fas fa-check me-1"></i>' +
                            escapeHtml(labelCopied);
                        setTimeout(function () {
                            copyBtn.innerHTML = orig;
                        }, 2000);
                    });
            });
        }

        if (regenForm) {
            regenForm.addEventListener("submit", function () {
                try {
                    localStorage.removeItem(storageKey);
                } catch (e) {
                    /* ignore */
                }
            });
        }
    })();

    const deleteSiteModal = document.getElementById("deleteSiteModal");
    if (deleteSiteModal && typeof jQuery !== "undefined") {
        jQuery(deleteSiteModal).on("show.bs.modal", function (event) {
            const btn = event.relatedTarget;
            if (!btn || !btn.getAttribute) {
                return;
            }
            const url = btn.getAttribute("data-delete-url");
            const name = btn.getAttribute("data-site-name");
            const form = document.getElementById("deleteSiteForm");
            const nameEl = document.getElementById("deleteSiteModalName");
            if (form && url) {
                form.setAttribute("action", url);
            }
            if (nameEl) {
                nameEl.textContent = name || "";
            }
        });
    }
})();
