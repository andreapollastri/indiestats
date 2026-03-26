import { getPaAnalyticsFilterParams } from './site-filters.js';

const POLL_MS = 2000;
const MAX_POLLS = 180;

function showFixedAlert(message, variant) {
    const wrap = document.createElement('div');
    wrap.className =
        'alert alert-' +
        variant +
        ' shadow position-fixed bottom-0 end-0 m-3 pa-export-toast';
    wrap.style.zIndex = '1080';
    wrap.setAttribute('role', 'alert');
    wrap.innerHTML =
        '<div class="d-flex align-items-start justify-content-between gap-2">' +
        '<span>' +
        message +
        '</span>' +
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
        '</div>';
    document.body.appendChild(wrap);
}

/**
 * @param {string} statusUrl
 * @param {(url: string) => void} onReady
 * @param {(msg: string) => void} onFailed
 */
function pollExportStatus(statusUrl, onReady, onFailed) {
    let n = 0;
    const tick = function () {
        if (n >= MAX_POLLS) {
            onFailed('Timeout');
            return;
        }
        n += 1;
        fetch(statusUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (d.status === 'completed' && d.download_url) {
                    onReady(d.download_url);
                    return;
                }
                if (d.status === 'failed') {
                    onFailed(d.error_message || '');
                    return;
                }
                setTimeout(tick, POLL_MS);
            })
            .catch(function () {
                setTimeout(tick, POLL_MS);
            });
    };
    tick();
}

function init() {
    const btn = document.getElementById('pa-site-export-btn');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', function () {
        const url = btn.dataset.paExportUrl;
        const csrf = btn.dataset.paCsrf;
        const range = btn.dataset.paRange;
        const pendingMsg = btn.dataset.paExportPending || '…';
        const readyMsg = btn.dataset.paExportReady || '';
        const failedPrefix = btn.dataset.paExportFailed || '';

        if (!url || !csrf || !range) {
            return;
        }

        const formData = new FormData();
        formData.append('range', range);
        const filters = getPaAnalyticsFilterParams();
        Object.keys(filters).forEach(function (k) {
            formData.append(k, filters[k]);
        });

        btn.disabled = true;
        const prevHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + pendingMsg;

        fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            credentials: 'same-origin',
            body: formData,
        })
            .then(function (r) {
                if (!r.ok) {
                    throw new Error(String(r.status));
                }
                return r.json();
            })
            .then(function (data) {
                const statusUrl = data && data.status_url;
                if (!statusUrl) {
                    throw new Error('no status');
                }
                pollExportStatus(
                    statusUrl,
                    function (downloadUrl) {
                        btn.disabled = false;
                        btn.innerHTML = prevHtml;
                        const msg =
                            readyMsg +
                            ' <a href="' +
                            downloadUrl +
                            '" class="alert-link fw-semibold">' +
                            'Download</a>';
                        showFixedAlert(msg, 'success');
                    },
                    function (err) {
                        btn.disabled = false;
                        btn.innerHTML = prevHtml;
                        showFixedAlert(
                            failedPrefix + (err ? ' ' + String(err) : ''),
                            'danger'
                        );
                    }
                );
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = prevHtml;
                showFixedAlert(failedPrefix, 'danger');
            });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
