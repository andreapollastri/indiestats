import { getPaAnalyticsFilterParams } from './site-filters.js';

function readRealtimeConfig() {
    const el = document.getElementById('pa-realtime-config');
    if (!el) {
        return null;
    }
    try {
        const parsed = JSON.parse(el.textContent.trim());
        if (parsed && typeof parsed === 'object') {
            return parsed;
        }
    } catch {
        // ignore
    }
    return null;
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

function formatRelativeTime(seconds, labels) {
    if (seconds < 15) {
        return labels.justNow || 'Now';
    }
    if (seconds < 60) {
        return (labels.secondsAgo || ':count s').replace(':count', String(seconds));
    }
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
        return (labels.minutesAgo || ':count min').replace(':count', String(minutes));
    }
    const hours = Math.floor(seconds / 3600);
    if (hours < 24) {
        return (labels.hoursAgo || ':count h').replace(':count', String(hours));
    }
    const days = Math.floor(seconds / 86400);
    if (days < 30) {
        return (labels.daysAgo || ':count gg').replace(':count', String(days));
    }
    const months = Math.floor(seconds / (30 * 86400));
    if (months < 12) {
        return (labels.monthsAgo || ':count mesi').replace(':count', String(months));
    }
    const years = Math.floor(seconds / (12 * 30 * 86400));
    return (labels.yearsAgo || ':count anni').replace(':count', String(years));
}

function formatUpdatedAt(iso, labels) {
    if (!iso) {
        return labels.loading || '';
    }
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) {
        return labels.loading || '';
    }
    const time = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    return (labels.updated || 'Updated :time').replace(':time', time);
}

function renderRecentList(items, labels) {
    const list = document.getElementById('pa-realtime-recent');
    if (!list) {
        return;
    }
    if (!Array.isArray(items) || items.length === 0) {
        list.innerHTML =
            '<li class="small pa-text-muted-soft">' + escapeHtml(labels.noActivity || 'No recent activity') + '</li>';
        return;
    }
    list.innerHTML = items
        .map(function (item) {
            const siteName = item.site_name
                ? '<span class="pa-realtime-recent__site">' + escapeHtml(item.site_name) + '</span>'
                : '';
            const country = item.country_code
                ? '<span class="pa-realtime-recent__country font-monospace">' +
                  escapeHtml(item.country_code) +
                  '</span>'
                : '';
            return (
                '<li class="pa-realtime-recent__item">' +
                siteName +
                '<span class="pa-realtime-recent__path text-truncate font-monospace" title="' +
                escapeHtml(item.path || '/') +
                '">' +
                escapeHtml(item.path || '/') +
                '</span>' +
                country +
                '<span class="pa-realtime-recent__time font-monospace">' +
                escapeHtml(item.time_ago || formatRelativeTime(item.seconds_ago || 0, labels)) +
                '</span>' +
                '</li>'
            );
        })
        .join('');
}

function formatSiteLiveCopy(active, pageviews, labels) {
    if ((active ?? 0) <= 0 && (pageviews ?? 0) <= 0) {
        return labels.siteLiveIdle || 'No active visitors';
    }
    return (labels.siteLive || ':active active · :pageviews views (5 min)')
        .replace(':active', String(active ?? 0))
        .replace(':pageviews', String(pageviews ?? 0));
}

function updateDashboardSiteCards(sites, labels) {
    if (!Array.isArray(sites)) {
        return;
    }
    sites.forEach(function (row) {
        const el = document.getElementById('pa-dashboard-site-live-' + row.id);
        if (!el) {
            return;
        }
        const copyEl = el.querySelector('.pa-site-card__live-copy');
        if (!copyEl) {
            return;
        }
        const active = row.active_visitors ?? 0;
        const pageviews = row.pageviews_last_5m ?? 0;
        copyEl.textContent = formatSiteLiveCopy(active, pageviews, labels);
        el.classList.toggle('pa-site-card__live--active', active > 0);
    });
}

function initRealtimeChart(canvas, labels) {
    const primary = 'rgb(16, 185, 129)';
    const primaryFill = 'rgba(16, 185, 129, 0.08)';
    const secondary = 'rgb(6, 182, 212)';

    return new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: labels.pageviews || 'Pageviews',
                    data: [],
                    borderColor: primary,
                    backgroundColor: primaryFill,
                    borderWidth: 1.5,
                    pointRadius: 0,
                    pointHoverRadius: 3,
                    fill: true,
                    tension: 0.35,
                },
                {
                    label: labels.visitors || 'Visitors',
                    data: [],
                    borderColor: secondary,
                    backgroundColor: 'transparent',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    pointHoverRadius: 3,
                    fill: false,
                    tension: 0.35,
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            animation: { duration: 300 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    intersect: false,
                    mode: 'index',
                    backgroundColor: '#0f172a',
                    titleFont: { family: "'JetBrains Mono', monospace", size: 10 },
                    bodyFont: { family: "'JetBrains Mono', monospace", size: 10 },
                    padding: 8,
                    cornerRadius: 6,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        maxRotation: 0,
                        maxTicksLimit: 8,
                        font: { size: 9, family: "'JetBrains Mono', monospace" },
                        color: '#94a3b8',
                    },
                    border: { display: false },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: { size: 9, family: "'JetBrains Mono', monospace" },
                        color: '#94a3b8',
                    },
                    grid: { color: '#f1f5f9' },
                    border: { display: false },
                },
            },
        },
    });
}

function updateRealtimeChart(chart, series) {
    if (!chart || !Array.isArray(series)) {
        return;
    }
    chart.data.labels = series.map(function (row) {
        return row.label;
    });
    chart.data.datasets[0].data = series.map(function (row) {
        return row.pageviews;
    });
    chart.data.datasets[1].data = series.map(function (row) {
        return row.visitors;
    });
    chart.resize();
    chart.update('none');
}

function applyRealtimePayload(payload, chart, labels, config) {
    const activeEl = document.getElementById('pa-realtime-active');
    const pageviewsEl = document.getElementById('pa-realtime-pageviews-5m');
    const updatedEl = document.getElementById('pa-realtime-updated');

    if (activeEl) {
        activeEl.textContent = String(payload.active_visitors ?? 0);
    }
    if (pageviewsEl) {
        pageviewsEl.textContent = String(payload.pageviews_last_5m ?? 0);
    }
    if (updatedEl) {
        updatedEl.textContent = formatUpdatedAt(payload.generated_at, labels);
    }

    updateRealtimeChart(chart, payload.series);
    renderRecentList(payload.recent, labels);

    if (config && config.updateSiteCards) {
        updateDashboardSiteCards(payload.sites, labels);
    }
}

function init() {
    const panel = document.getElementById('pa-realtime-panel');
    const config = readRealtimeConfig();
    const canvas = document.getElementById('pa-realtime-chart');
    if (!panel || !config || !config.url || !canvas) {
        return;
    }

    const labels = config.labels || {};
    let chart = null;
    let timer = null;
    let inFlight = false;

    function ensureChart() {
        if (chart || typeof Chart === 'undefined') {
            return chart;
        }
        chart = initRealtimeChart(canvas, labels);
        return chart;
    }

    function buildRealtimeUrl() {
        const url = new URL(config.url, window.location.origin);
        const filters = getPaAnalyticsFilterParams();
        Object.keys(filters).forEach(function (key) {
            url.searchParams.set(key, filters[key]);
        });
        return url.toString();
    }

    function fetchRealtime() {
        if (inFlight || document.hidden) {
            return;
        }
        inFlight = true;
        fetch(buildRealtimeUrl(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('realtime fetch failed');
                }
                return response.json();
            })
            .then(function (payload) {
                ensureChart();
                applyRealtimePayload(payload, chart, labels, config);
            })
            .catch(function () {
                const updatedEl = document.getElementById('pa-realtime-updated');
                if (updatedEl) {
                    updatedEl.textContent = labels.loading || 'Loading…';
                }
            })
            .finally(function () {
                inFlight = false;
            });
    }

    function startPolling() {
        if (timer) {
            clearInterval(timer);
        }
        fetchRealtime();
        timer = setInterval(fetchRealtime, config.pollMs || 15000);
    }

    if (typeof Chart === 'undefined') {
        const waitForChart = function () {
            if (typeof Chart === 'undefined') {
                requestAnimationFrame(waitForChart);
                return;
            }
            startPolling();
        };
        waitForChart();
    } else {
        startPolling();
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            fetchRealtime();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
