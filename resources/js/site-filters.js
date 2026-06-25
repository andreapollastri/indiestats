import TomSelect from 'tom-select';

const TYPE_TO_PARAM = {
    source: 'filter_source',
    path: 'filter_path',
    page_title: 'filter_page_title',
    page_query: 'filter_page_query',
    utm: 'filter_utm_source',
    utm_source: 'filter_utm_source',
    utm_medium: 'filter_utm_medium',
    utm_campaign: 'filter_utm_campaign',
    utm_term: 'filter_utm_term',
    utm_content: 'filter_utm_content',
    gclid: 'filter_gclid',
    fbclid: 'filter_fbclid',
    msclkid: 'filter_msclkid',
    event: 'filter_event',
    device: 'filter_device',
    browser: 'filter_browser',
    browser_version: 'filter_browser_version',
    os: 'filter_os',
    language: 'filter_language',
    timezone: 'filter_timezone',
    session_id: 'filter_session_id',
    is_bot: 'filter_is_bot',
    country: 'filter_country',
    asn: 'filter_asn',
};

const FILTER_PARAM_KEYS = [
    'filter_source',
    'filter_path',
    'filter_page_title',
    'filter_page_query',
    'filter_utm',
    'filter_utm_source',
    'filter_utm_medium',
    'filter_utm_campaign',
    'filter_utm_term',
    'filter_utm_content',
    'filter_gclid',
    'filter_fbclid',
    'filter_msclkid',
    'filter_event',
    'filter_device',
    'filter_browser',
    'filter_browser_version',
    'filter_os',
    'filter_language',
    'filter_timezone',
    'filter_session_id',
    'filter_is_bot',
    'filter_country',
    'filter_asn',
    'filter_q',
];

export function readFilterConfig() {
    const el = document.getElementById('pa-filter-config');
    if (!el) {
        return null;
    }
    try {
        return JSON.parse(el.textContent.trim());
    } catch {
        return null;
    }
}

/**
 * `filter_*` parameters for AJAX DataTables: read only from the visible query string (location).
 * Do not use JSON `cfg.current`: after navigation or URL changes without reload it could hold
 * a stale merge and table totals would not match the active filters.
 */
export function getPaAnalyticsFilterParams() {
    const out = {};
    try {
        const params = new URLSearchParams(window.location.search);
        FILTER_PARAM_KEYS.forEach(function (k) {
            const v = params.get(k);
            if (v !== null && v !== '') {
                out[k] = v;
            }
        });
    } catch {
        // ignore
    }
    return out;
}

let filterFormSubmitWired = false;

function wireFilterFormSubmit() {
    if (filterFormSubmitWired) {
        return;
    }
    const form = document.getElementById('pa-site-filters-form');
    if (!form) {
        return;
    }
    filterFormSubmitWired = true;
    form.addEventListener(
        'submit',
        function () {
            document.querySelectorAll('select.pa-ts-filter').forEach(function (sel) {
                const ts = sel.tomselect;
                if (ts && typeof ts.updateOriginalInput === 'function') {
                    ts.updateOriginalInput({ silent: true });
                }
            });
        },
        true
    );
}

function mergePresetOptions(presets, currentValue, currentLabel) {
    const seen = new Set();
    const opts = [];
    (presets || []).forEach(function (p) {
        if (p == null || p.value === null || p.value === undefined || p.value === '') {
            return;
        }
        const value = String(p.value);
        if (seen.has(value)) {
            return;
        }
        seen.add(value);
        opts.push({ value: value, text: p.text != null ? String(p.text) : value });
    });
    if (currentValue && !seen.has(currentValue)) {
        opts.push({
            value: currentValue,
            text: currentLabel != null && String(currentLabel) !== '' ? String(currentLabel) : currentValue,
        });
    }
    return opts;
}

function init() {
    wireFilterFormSubmit();

    const cfg = readFilterConfig();
    if (!cfg || !cfg.optionsUrl || !cfg.range) {
        return;
    }

    const presetsByType = cfg.presets && typeof cfg.presets === 'object' ? cfg.presets : {};
    const current = cfg.current && typeof cfg.current === 'object' ? cfg.current : {};
    const currentLabels = cfg.currentLabels && typeof cfg.currentLabels === 'object' ? cfg.currentLabels : {};

    document.querySelectorAll('select.pa-ts-filter').forEach(function (select) {
        const type = select.dataset.paFilterType;
        if (!type || !TYPE_TO_PARAM[type]) {
            return;
        }

        const param = TYPE_TO_PARAM[type];
        const currentVal = current[param] ? String(current[param]) : '';
        const presets = presetsByType[type];
        const initialOptions = mergePresetOptions(presets, currentVal, currentLabels[param]);

        const placeholder = select.getAttribute('placeholder') || '';

        // Empty options labeled "Tutti" / "Tutte" prevent the Tom Select placeholder from showing.
        select.querySelectorAll('option[value=""]').forEach(function (opt) {
            opt.textContent = '';
        });

        let ts;
        try {
            ts = new TomSelect(select, {
                valueField: 'value',
                labelField: 'text',
                searchField: ['text', 'value'],
                options: initialOptions,
                items: currentVal ? [currentVal] : [],
                maxItems: 1,
                create: false,
                allowEmptyOption: true,
                placeholder: placeholder,
                openOnFocus: true,
                preload: 'focus',
                /**
                 * dropdown_input: search field in the panel (otherwise with a selected value Tom Select
                 * hides the input so typing is impossible and load() never runs).
                 * clear_button: button to go back to “All” and search again.
                 */
                plugins: [
                    'caret_position',
                    'dropdown_input',
                    { name: 'clear_button', options: { title: 'Clear filter' } },
                ],
                loadThrottle: 300,
                // Do not use document.body: Tom Select only applies top/left when dropdownParent === 'body' (string);
                // with an HTMLElement, positionDropdown() does not run and the menu is misplaced.
                // Default: parent = .ts-wrapper wrapper (position: relative) → dropdown below the field.
                load: function (query, callback) {
                    const url = new URL(cfg.optionsUrl, window.location.origin);
                    url.searchParams.set('type', type);
                    url.searchParams.set('range', cfg.range);
                    const q = query != null ? String(query).trim() : '';
                    if (q.length) {
                        url.searchParams.set('q', q);
                    }
                    fetch(url.toString(), {
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
                            const results = data && Array.isArray(data.results) ? data.results : [];
                            callback(results);
                        })
                        .catch(function () {
                            callback();
                        });
                },
            });
        } catch (err) {
            console.error('[pa-site-filters] Tom Select init failed:', type, err);
            return;
        }

        if (!currentVal) {
            ts.clear(true);
        }
    });

    const filterCollapse = document.getElementById('pa-site-filters-collapse');
    if (filterCollapse) {
        filterCollapse.addEventListener('shown.bs.collapse', function () {
            document.querySelectorAll('select.pa-ts-filter').forEach(function (select) {
                const ts = select.tomselect;
                if (ts && typeof ts.positionDropdown === 'function') {
                    ts.positionDropdown();
                }
            });
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
