import jsVectorMap from 'jsvectormap';
import 'jsvectormap/dist/jsvectormap.min.css';
import 'jsvectormap/dist/maps/world.js';
import { getPaAnalyticsFilterParams } from './site-filters.js';

const COLOR_LOW = [236, 253, 245];
const COLOR_HIGH = [4, 120, 87];

function readCountryMapConfig() {
    const el = document.getElementById('pa-country-map-config');
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

function formatNumber(n) {
    const num = Number(n);
    if (!Number.isFinite(num)) {
        return '0';
    }
    return num.toLocaleString();
}

function interpolateColor(ratio) {
    const t = Math.max(0, Math.min(1, ratio));
    const r = Math.round(COLOR_LOW[0] + (COLOR_HIGH[0] - COLOR_LOW[0]) * t);
    const g = Math.round(COLOR_LOW[1] + (COLOR_HIGH[1] - COLOR_LOW[1]) * t);
    const b = Math.round(COLOR_LOW[2] + (COLOR_HIGH[2] - COLOR_LOW[2]) * t);

    return (
        '#' +
        [r, g, b]
            .map(function (channel) {
                const hex = channel.toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            })
            .join('')
    );
}

function buildFillValues(countries) {
    const entries = Object.entries(countries || {});
    if (entries.length === 0) {
        return { values: {}, scale: {} };
    }

    const pageviews = entries.map(function (entry) {
        return entry[1].pageviews || 0;
    });
    const min = Math.min.apply(null, pageviews);
    const max = Math.max.apply(null, pageviews);
    const values = {};
    const scale = {};

    entries.forEach(function (entry) {
        const code = entry[0];
        const meta = entry[1];
        const count = meta.pageviews || 0;
        const ratio = max === min ? 1 : (count - min) / (max - min);
        const color = interpolateColor(ratio);
        values[code] = color;
        scale[color] = color;
    });

    return { values: values, scale: scale };
}

function buildMapUrl(config) {
    const url = new URL(config.url, window.location.origin);
    url.searchParams.set('range', config.range || '7d');
    const filters = getPaAnalyticsFilterParams();
    Object.keys(filters).forEach(function (key) {
        url.searchParams.set(key, filters[key]);
    });
    return url.toString();
}

function init() {
    const container = document.getElementById('pa-country-map');
    const config = readCountryMapConfig();
    if (!container || !config || !config.url) {
        return;
    }

    const labels = config.labels || {};
    let mapInstance = null;
    let countryMeta = {};
    let loaded = false;
    let loading = false;

    function renderEmpty(message) {
        container.innerHTML =
            '<p class="small pa-text-muted-soft text-center mb-0 py-4">' + escapeHtml(message) + '</p>';
    }

    function renderMap(payload) {
        countryMeta = payload.countries || {};
        const entries = Object.entries(countryMeta);
        if (entries.length === 0) {
            renderEmpty(labels.empty || 'No geographic data in this period');
            return;
        }

        container.innerHTML = '';
        const { values, scale } = buildFillValues(countryMeta);

        if (mapInstance) {
            mapInstance.destroy();
            mapInstance = null;
        }

        mapInstance = new jsVectorMap({
            selector: '#pa-country-map',
            map: 'world',
            draggable: true,
            zoomButtons: true,
            zoomOnScroll: false,
            bindTouchEvents: true,
            regionStyle: {
                initial: {
                    fill: '#e2e8f0',
                    fillOpacity: 1,
                    stroke: '#ffffff',
                    strokeWidth: 0.6,
                },
                hover: {
                    fillOpacity: 0.85,
                    cursor: 'pointer',
                },
            },
            series: {
                regions: [
                    {
                        attribute: 'fill',
                        values: values,
                        scale: scale,
                    },
                ],
            },
            onRegionTooltipShow: function (event, tooltip, code) {
                const meta = countryMeta[code];
                if (!meta) {
                    return;
                }
                event.preventDefault();
                tooltip.text(
                    '<strong>' +
                        escapeHtml(meta.label || code) +
                        '</strong><br>' +
                        escapeHtml(labels.pageviews || 'Pageviews') +
                        ': ' +
                        escapeHtml(formatNumber(meta.pageviews)) +
                        '<br>' +
                        escapeHtml(labels.visitors || 'Visitors') +
                        ': ' +
                        escapeHtml(formatNumber(meta.visitors)),
                    true
                );
            },
        });

        if (typeof mapInstance.updateSize === 'function') {
            mapInstance.updateSize();
        }
    }

    function loadMap() {
        if (loading) {
            return;
        }
        loading = true;
        container.innerHTML =
            '<p class="small pa-text-muted-soft text-center mb-0 py-4">' +
            escapeHtml(labels.loading || 'Loading…') +
            '</p>';

        fetch(buildMapUrl(config), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('country map fetch failed');
                }
                return response.json();
            })
            .then(function (payload) {
                loaded = true;
                renderMap(payload);
            })
            .catch(function () {
                renderEmpty(labels.empty || 'No geographic data in this period');
            })
            .finally(function () {
                loading = false;
            });
    }

    const geoSection = document.getElementById('pa-stats-section-geo-body');
    if (geoSection) {
        geoSection.addEventListener('shown.bs.collapse', function () {
            if (!loaded) {
                loadMap();
                return;
            }
            if (mapInstance && typeof mapInstance.updateSize === 'function') {
                mapInstance.updateSize();
            }
        });
        if (geoSection.classList.contains('show')) {
            loadMap();
        }
    } else {
        loadMap();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
