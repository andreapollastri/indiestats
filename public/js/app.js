/**
 * App helpers: clipboard copy, 2FA QR / recovery fetch (SB Admin 2 loads jQuery globally).
 */
(function () {
    'use strict';

    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.getAttribute('data-copy');
            if (!text || !navigator.clipboard) return;
            navigator.clipboard.writeText(text).then(function () {
                var orig = btn.innerHTML;
                var done = btn.getAttribute('data-copy-done') || 'Copiato';
                btn.innerHTML = '<i class="fas fa-check mr-1"></i>' + done;
                setTimeout(function () {
                    btn.innerHTML = orig;
                }, 2000);
            });
        });
    });

    var qr = document.getElementById('pa-two-factor-qr');
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

    var rc = document.getElementById('pa-recovery-codes');
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

    var deleteSiteModal = document.getElementById('deleteSiteModal');
    if (deleteSiteModal && typeof jQuery !== 'undefined') {
        jQuery(deleteSiteModal).on('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn || !btn.getAttribute) {
                return;
            }
            var url = btn.getAttribute('data-delete-url');
            var name = btn.getAttribute('data-site-name');
            var form = document.getElementById('deleteSiteForm');
            var nameEl = document.getElementById('deleteSiteModalName');
            if (form && url) {
                form.setAttribute('action', url);
            }
            if (nameEl) {
                nameEl.textContent = name || '';
            }
        });
    }
})();
