import TomSelect from 'tom-select';

const TYPE_TO_PARAM = {
    source: 'filter_source',
    path: 'filter_path',
    utm: 'filter_utm',
    event: 'filter_event',
    device: 'filter_device',
    country: 'filter_country',
};

const FILTER_PARAM_KEYS = [
    'filter_source',
    'filter_path',
    'filter_utm',
    'filter_event',
    'filter_device',
    'filter_country',
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
 * Parametri `filter_*` per AJAX DataTables: solo dalla query string visibile (location).
 * Non usare il JSON `cfg.current`: dopo navigazione o modifica URL senza reload poteva restare
 * un merge obsoleto e i totali delle tabelle non coincidevano con i filtri effettivi.
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

function mergePresetOptions(presets, currentValue) {
    const seen = new Set();
    const opts = [];
    (presets || []).forEach(function (p) {
        if (!p || typeof p.value !== 'string') {
            return;
        }
        if (seen.has(p.value)) {
            return;
        }
        seen.add(p.value);
        opts.push({ value: p.value, text: p.text != null ? String(p.text) : p.value });
    });
    if (currentValue && !seen.has(currentValue)) {
        opts.push({ value: currentValue, text: currentValue });
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

    document.querySelectorAll('select.pa-ts-filter').forEach(function (select) {
        const type = select.dataset.paFilterType;
        if (!type || !TYPE_TO_PARAM[type]) {
            return;
        }

        const param = TYPE_TO_PARAM[type];
        const currentVal = current[param] ? String(current[param]) : '';
        const presets = presetsByType[type];
        const initialOptions = mergePresetOptions(presets, currentVal);

        const placeholder = select.getAttribute('placeholder') || '';

        new TomSelect(select, {
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
            /**
             * dropdown_input: campo di ricerca nel pannello (altrimenti con un valore selezionato
             * Tom Select nasconde l’input e non si può digitare → load() non parte).
             * clear_button: pulsante per tornare a «Tutte» e cercare di nuovo.
             */
            plugins: [
                'caret_position',
                'dropdown_input',
                { name: 'clear_button', options: { title: 'Azzera filtro' } },
            ],
            loadThrottle: 300,
            // Non usare document.body: Tom Select applica top/left solo se dropdownParent === 'body' (stringa);
            // con HTMLElement positionDropdown() non viene eseguito e il menu finisce fuori posto.
            // Default: parent = wrapper .ts-wrapper (position: relative) → dropdown sotto il campo.
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
