import jQuery from 'jquery';
import * as bootstrap from 'bootstrap';
import { Chart, registerables } from 'chart.js';

window.$ = window.jQuery = jQuery;
window.bootstrap = bootstrap;
Chart.register(...registerables);
window.Chart = Chart;

(function () {
    'use strict';

    jQuery(function () {
        jQuery('#sidebarToggle, #sidebarToggleTop').on('click', function (e) {
            e.preventDefault();
            jQuery('body').toggleClass('sidebar-toggled');
            jQuery('.sidebar').toggleClass('toggled');
        });

        jQuery(window).on('resize', function () {
            if (jQuery(window).width() < 768) {
                jQuery('body').removeClass('sidebar-toggled');
                jQuery('.sidebar').removeClass('toggled');
            }
        });

        jQuery(document).on('scroll', function () {
            const st = jQuery(this).scrollTop();
            if (st > 100) {
                jQuery('.scroll-to-top').fadeIn();
            } else {
                jQuery('.scroll-to-top').fadeOut();
            }
        });

        jQuery('.scroll-to-top').on('click', function (e) {
            e.preventDefault();
            jQuery('html, body').animate({ scrollTop: 0 }, 500);
        });
    });

    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const text = btn.getAttribute('data-copy');
            if (!text || !navigator.clipboard) return;
            navigator.clipboard.writeText(text).then(function () {
                const orig = btn.innerHTML;
                const done = btn.getAttribute('data-copy-done') || 'Copiato';
                btn.innerHTML = '<i class="fas fa-check me-1"></i>' + done;
                setTimeout(function () {
                    btn.innerHTML = orig;
                }, 2000);
            });
        });
    });

    const qr = document.getElementById('pa-two-factor-qr');
    if (qr && qr.dataset.qrUrl) {
        fetch(qr.dataset.qrUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (data && data.svg) qr.innerHTML = data.svg;
            })
            .catch(function () {});
    }

    const rc = document.getElementById('pa-recovery-codes');
    if (rc && rc.dataset.codesUrl) {
        fetch(rc.dataset.codesUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (codes) {
                if (!Array.isArray(codes) || !codes.length) return;
                rc.innerHTML = codes
                    .map(function (c) {
                        return '<code class="d-block mb-1">' + c + '</code>';
                    })
                    .join('');
            })
            .catch(function () {});
    }

    const deleteSiteModal = document.getElementById('deleteSiteModal');
    if (deleteSiteModal && typeof jQuery !== 'undefined') {
        jQuery(deleteSiteModal).on('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn || !btn.getAttribute) {
                return;
            }
            const url = btn.getAttribute('data-delete-url');
            const name = btn.getAttribute('data-site-name');
            const form = document.getElementById('deleteSiteForm');
            const nameEl = document.getElementById('deleteSiteModalName');
            if (form && url) {
                form.setAttribute('action', url);
            }
            if (nameEl) {
                nameEl.textContent = name || '';
            }
        });
    }
})();
