import { getPaAnalyticsFilterParams } from './site-filters.js';

function readConfig() {
    const el = document.getElementById('pa-asn-profiles-config');
    if (!el) {
        return null;
    }
    try {
        return JSON.parse(el.textContent.trim());
    } catch {
        return null;
    }
}

function escapeHtml(s) {
    if (s === null || s === undefined) {
        return '';
    }
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function readRange() {
    try {
        return new URLSearchParams(window.location.search).get('range') || '7d';
    } catch {
        return '7d';
    }
}

function formatDateTime(iso) {
    if (!iso) {
        return '—';
    }
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) {
        return '—';
    }
    return date.toLocaleString([], {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function buildVisitorsUrl(config, asn) {
    const url = new URL(
        config.visitorsUrlTemplate.replace('__ASN__', String(asn)),
        window.location.origin,
    );
    url.searchParams.set('range', readRange());
    const filters = getPaAnalyticsFilterParams();
    Object.keys(filters).forEach(function (key) {
        url.searchParams.set(key, filters[key]);
    });
    return url.toString();
}

function buildTimelineUrl(config, visitorId, asn) {
    const url = new URL(
        config.timelineUrlTemplate.replace('__VISITOR__', encodeURIComponent(visitorId)),
        window.location.origin,
    );
    url.searchParams.set('range', readRange());
    url.searchParams.set('asn', String(asn));
    const filters = getPaAnalyticsFilterParams();
    Object.keys(filters).forEach(function (key) {
        url.searchParams.set(key, filters[key]);
    });
    return url.toString();
}

function chip(label, value) {
    if (!value) {
        return '';
    }
    return (
        '<span class="pa-visitor-profile__chip">' +
        '<span class="pa-visitor-profile__chip-label">' +
        escapeHtml(label) +
        '</span> ' +
        '<span class="pa-visitor-profile__chip-value">' +
        escapeHtml(value) +
        '</span></span>'
    );
}

function renderSummary(summary, labels) {
    const chips = [
        chip(labels.pageviews, summary.pageviews),
        chip(labels.events, summary.events),
        chip(labels.outbounds, summary.outbounds),
        chip(labels.visitDays, summary.visit_days),
        chip(labels.browser, summary.browser),
        chip(labels.os, summary.os),
        chip(labels.device, summary.device_type),
        chip(
            labels.country,
            summary.country_label || summary.country_code || null,
        ),
        chip(labels.ip, summary.ip_hint),
    ].filter(Boolean);

    const meta = [
        '<div class="pa-visitor-profile__stats">' + chips.join('') + '</div>',
        '<div class="small pa-text-muted-soft mt-2">',
        escapeHtml(labels.firstSeen) +
            ': <span class="font-monospace">' +
            escapeHtml(formatDateTime(summary.first_seen)) +
            '</span> · ' +
            escapeHtml(labels.lastSeen) +
            ': <span class="font-monospace">' +
            escapeHtml(formatDateTime(summary.last_seen)) +
            '</span>',
        '</div>',
    ];

    if (summary.ip_varies) {
        meta.push(
            '<div class="small pa-text-muted-soft mt-1">' +
                escapeHtml(labels.ipVaries) +
                '</div>',
        );
    }

    return meta.join('');
}

function renderTimelineItem(item, labels) {
    const kindLabel =
        item.kind === 'pageview'
            ? labels.pageview
            : item.kind === 'event'
              ? labels.event
              : labels.outbound;

    let body = '';

    if (item.kind === 'pageview') {
        body =
            '<div class="pa-visitor-timeline__path font-monospace">' +
            escapeHtml(item.path || '—') +
            '</div>' +
            '<div class="pa-visitor-timeline__meta small pa-text-muted-soft">' +
            escapeHtml(labels.duration) +
            ': ' +
            escapeHtml(item.duration || '—');
        if (item.referrer_source) {
            body +=
                ' · ' +
                escapeHtml(labels.referrer) +
                ': ' +
                escapeHtml(item.referrer_source);
        }
        if (item.browser || item.os || item.device_type) {
            body +=
                ' · ' +
                [item.browser, item.os, item.device_type]
                    .filter(Boolean)
                    .map(escapeHtml)
                    .join(' / ');
        }
        if (item.country_label || item.country_code) {
            body +=
                ' · ' +
                escapeHtml(item.country_label || item.country_code);
        }
        if (item.ip_hint) {
            body += ' · ' + escapeHtml(labels.ip) + ': ' + escapeHtml(item.ip_hint);
        }
        body += '</div>';
    } else if (item.kind === 'event') {
        body =
            '<div class="pa-visitor-timeline__path font-monospace">' +
            escapeHtml(item.name || '—') +
            '</div>';
        if (item.path) {
            body +=
                '<div class="small pa-text-muted-soft">' +
                escapeHtml(labels.path) +
                ': ' +
                escapeHtml(item.path) +
                '</div>';
        }
        if (Array.isArray(item.properties) && item.properties.length) {
            body +=
                '<ul class="list-unstyled small font-monospace mb-0 mt-1">' +
                item.properties
                    .map(function (prop) {
                        return (
                            '<li><span class="text-muted">' +
                            escapeHtml(prop.key) +
                            '</span>: ' +
                            escapeHtml(prop.value) +
                            '</li>'
                        );
                    })
                    .join('') +
                '</ul>';
        }
    } else {
        body =
            '<div class="small pa-text-muted-soft">' +
            escapeHtml(labels.path) +
            ': <span class="font-monospace">' +
            escapeHtml(item.from_path || '—') +
            '</span></div>' +
            '<div class="pa-visitor-timeline__path font-monospace text-break">' +
            escapeHtml(item.target_url || '—') +
            '</div>';
    }

    return (
        '<li class="pa-visitor-timeline__item pa-visitor-timeline__item--' +
        escapeHtml(item.kind) +
        '">' +
        '<div class="pa-visitor-timeline__time font-monospace">' +
        escapeHtml(item.at || '') +
        '</div>' +
        '<div class="pa-visitor-timeline__body">' +
        '<div class="pa-visitor-timeline__kind">' +
        escapeHtml(kindLabel) +
        '</div>' +
        body +
        '</div></li>'
    );
}

function renderTimeline(days, labels) {
    if (!Array.isArray(days) || !days.length) {
        return (
            '<p class="small pa-text-muted-soft mb-0">' +
            escapeHtml('—') +
            '</p>'
        );
    }

    return days
        .map(function (day) {
            const items = (day.items || [])
                .map(function (item) {
                    return renderTimelineItem(item, labels);
                })
                .join('');

            return (
                '<section class="pa-visitor-timeline-day mb-4">' +
                '<h6 class="pa-visitor-timeline-day__title">' +
                escapeHtml(day.date_label || day.date || '') +
                '</h6>' +
                '<ol class="pa-visitor-timeline list-unstyled mb-0">' +
                items +
                '</ol></section>'
            );
        })
        .join('');
}

function init() {
    const config = readConfig();
    const modalEl = document.getElementById('paAsnProfilesModal');
    if (!config || !modalEl || !window.bootstrap) {
        return;
    }

    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    const labels = config.labels || {};
    const state = {
        asn: null,
        asnLabel: '',
        visitors: [],
        index: 0,
    };

    const els = {
        loading: document.getElementById('pa-asn-profiles-loading'),
        empty: document.getElementById('pa-asn-profiles-empty'),
        error: document.getElementById('pa-asn-profiles-error'),
        content: document.getElementById('pa-asn-profiles-content'),
        asnLabel: document.getElementById('pa-asn-profiles-asn-label'),
        counter: document.getElementById('pa-asn-profiles-counter'),
        summary: document.getElementById('pa-asn-profiles-summary'),
        visitorId: document.getElementById('pa-asn-profiles-visitor-id'),
        timeline: document.getElementById('pa-asn-profiles-timeline'),
        truncated: document.getElementById('pa-asn-profiles-truncated'),
        prev: document.getElementById('pa-asn-profiles-prev'),
        next: document.getElementById('pa-asn-profiles-next'),
    };

    function setVisible(section) {
        els.loading.classList.toggle('d-none', section !== 'loading');
        els.empty.classList.toggle('d-none', section !== 'empty');
        els.error.classList.toggle('d-none', section !== 'error');
        els.content.classList.toggle('d-none', section !== 'content');
    }

    function showError(message) {
        els.error.textContent = message;
        setVisible('error');
    }

    function updateNav() {
        const total = state.visitors.length;
        const current = total ? state.index + 1 : 0;
        els.counter.textContent = (labels.profileCounter || 'Profilo :current di :total')
            .replace(':current', String(current))
            .replace(':total', String(total));
        if (els.prev) {
            els.prev.disabled = state.index <= 0;
        }
        if (els.next) {
            els.next.disabled = state.index >= total - 1;
        }
    }

    function loadTimeline() {
        const visitor = state.visitors[state.index];
        if (!visitor) {
            return;
        }

        setVisible('loading');
        els.error.classList.add('d-none');

        fetch(buildTimelineUrl(config, visitor.visitor_id, state.asn), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('timeline failed');
                }
                return response.json();
            })
            .then(function (payload) {
                els.asnLabel.textContent = state.asnLabel;
                els.visitorId.textContent = payload.visitor_id || visitor.visitor_id;
                els.summary.innerHTML = renderSummary(payload.summary || {}, labels);
                els.timeline.innerHTML = renderTimeline(payload.days || [], labels);
                els.truncated.classList.toggle('d-none', !payload.truncated);
                updateNav();
                setVisible('content');
            })
            .catch(function () {
                showError(labels.timelineFailed || 'Impossibile caricare la timeline.');
            });
    }

    function openProfiles(asn, asnLabel) {
        state.asn = asn;
        state.asnLabel = asnLabel;
        state.visitors = [];
        state.index = 0;
        modal.show();
        setVisible('loading');
        els.error.classList.add('d-none');

        fetch(buildVisitorsUrl(config, asn), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('visitors failed');
                }
                return response.json();
            })
            .then(function (payload) {
                state.asnLabel = payload.label || asnLabel;
                state.visitors = Array.isArray(payload.visitors) ? payload.visitors : [];
                if (!state.visitors.length) {
                    els.asnLabel.textContent = state.asnLabel;
                    setVisible('empty');
                    return;
                }
                loadTimeline();
            })
            .catch(function () {
                showError(labels.loadFailed || 'Impossibile caricare i profili.');
            });
    }

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('.pa-asn-profile-trigger');
        if (!trigger) {
            return;
        }
        event.preventDefault();
        const asn = Number(trigger.dataset.asn);
        if (!Number.isFinite(asn) || asn <= 0) {
            return;
        }
        openProfiles(asn, trigger.dataset.label || '');
    });

    if (els.prev) {
        els.prev.addEventListener('click', function () {
            if (state.index <= 0) {
                return;
            }
            state.index -= 1;
            loadTimeline();
        });
    }

    if (els.next) {
        els.next.addEventListener('click', function () {
            if (state.index >= state.visitors.length - 1) {
                return;
            }
            state.index += 1;
            loadTimeline();
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

export { init };
