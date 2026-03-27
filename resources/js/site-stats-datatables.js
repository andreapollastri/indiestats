import DataTable from 'datatables.net-responsive-bs5';
import { getPaAnalyticsFilterParams } from './site-filters.js';

/** Fallback if the layout script tag is missing (e.g. tests). */
const DEFAULT_DATATABLES_LANGUAGE = {
    emptyTable: 'No data available in table',
    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
    infoEmpty: 'Showing 0 to 0 of 0 entries',
    infoFiltered: '(filtered from _MAX_ total entries)',
    infoThousands: ',',
    decimal: '.',
    lengthMenu: 'Show _MENU_ entries',
    loadingRecords: 'Loading...',
    processing: 'Processing...',
    search: 'Search:',
    zeroRecords: 'No matching records found',
    paginate: {
        first: 'First',
        last: 'Last',
        next: 'Next',
        previous: 'Previous',
    },
    aria: {
        orderable: 'Orderable',
        orderableReverse: 'Orderable (reverse order)',
        orderableRemove: 'Orderable (remove ordering)',
        sortAscending: ': activate to sort column ascending',
        sortDescending: ': activate to sort column descending',
    },
};

function readDatatablesLanguage() {
    const el = document.getElementById('pa-datatables-language');
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

function tableConfig(type, csrf, tableEl) {
    const dtLang = readDatatablesLanguage() || DEFAULT_DATATABLES_LANGUAGE;

    const num = (a, b) => [
        { data: a, className: !b ? 'font-monospace' : '' },
        { data: 'pageviews', className: 'text-end font-monospace' },
        { data: 'visitors', className: 'text-end font-monospace' },
    ];

    const base = {
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 10,
        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100],
        ],
        order: [[1, 'desc']],
        language: dtLang,
    };

    switch (type) {
        case 'paths':
            return {
                ...base,
                columns: num('path', false),
            };
        case 'utm':
        case 'utm_source':
            return {
                ...base,
                columns: num('utm_source', true),
            };
        case 'utm_medium':
            return {
                ...base,
                columns: num('utm_medium', true),
            };
        case 'utm_campaign':
            return {
                ...base,
                columns: num('utm_campaign', true),
            };
        case 'utm_term':
            return {
                ...base,
                columns: num('utm_term', true),
            };
        case 'utm_content':
            return {
                ...base,
                columns: num('utm_content', true),
            };
        case 'search':
            return {
                ...base,
                columns: num('query', true),
            };
        case 'source':
            return {
                ...base,
                columns: num('source', true),
            };
        case 'outbound':
            return {
                ...base,
                order: [[2, 'desc']],
                columns: [
                    {
                        data: 'target_url',
                        className: 'font-monospace',
                        render: function (data, cellType, row) {
                            if (cellType !== 'display' && cellType !== 'filter') {
                                return row.target_url || '';
                            }
                            const href = row.target_url || '';
                            if (!href) {
                                return '—';
                            }
                            return (
                                '<a href="' +
                                escapeHtml(href) +
                                '" target="_blank" rel="noopener noreferrer" class="text-break">' +
                                escapeHtml(href) +
                                '</a>'
                            );
                        },
                    },
                    {
                        data: 'referrer_source',
                        className: 'text-nowrap small',
                        render: function (d) {
                            return escapeHtml(d || '—');
                        },
                    },
                    { data: 'pageviews', className: 'text-end font-monospace' },
                    { data: 'visitors', className: 'text-end font-monospace' },
                ],
            };
        case 'browser':
        case 'device':
            return {
                ...base,
                columns: num('name', true),
            };
        case 'country':
            return {
                ...base,
                order: [[1, 'desc']],
                columns: [
                    {
                        data: null,
                        orderable: true,
                        render: function (data, cellType, row) {
                            if (cellType !== 'display' && cellType !== 'filter') {
                                return row.country_label || '';
                            }
                            const code = row.country_code
                                ? '<span class="text-muted font-monospace">(' +
                                  escapeHtml(row.country_code) +
                                  ')</span>'
                                : '';
                            return escapeHtml(row.country_label || '') + (code ? ' ' + code : '');
                        },
                    },
                    { data: 'pageviews', className: 'text-end font-monospace' },
                    { data: 'visitors', className: 'text-end font-monospace' },
                ],
            };
        case 'event_names':
            return {
                ...base,
                order: [[1, 'desc']],
                columns: [
                    { data: 'name', className: 'font-monospace' },
                    { data: 'count', className: 'text-end font-monospace' },
                    { data: 'visitors', className: 'text-end font-monospace' },
                ],
            };
        case 'events':
            return {
                ...base,
                order: [[0, 'desc']],
                columnDefs: [
                    { responsivePriority: 1, targets: 0 },
                    { responsivePriority: 2, targets: 1 },
                    { responsivePriority: 3, targets: 2 },
                    { responsivePriority: 4, targets: 3 },
                    { responsivePriority: 10, targets: 4 },
                ],
                columns: [
                    {
                        data: 'created_at',
                        className: 'text-nowrap font-monospace pa-col-datetime pa-dt-events-cell',
                    },
                    { data: 'name', className: 'font-monospace pa-dt-events-cell' },
                    {
                        data: 'visitor_id',
                        className: 'pa-dt-visitor-id pa-dt-events-cell',
                        render: function (d, cellType) {
                            if (cellType !== 'display' && cellType !== 'filter') {
                                return d || '';
                            }
                            const id = d || '—';
                            return (
                                '<span class="d-inline-block font-monospace text-break user-select-all pa-dt-visitor-id-inner">' +
                                escapeHtml(id) +
                                '</span>'
                            );
                        },
                    },
                    {
                        data: 'path',
                        className: 'font-monospace pa-dt-events-cell',
                        render: function (d) {
                            return escapeHtml(d || '—');
                        },
                    },
                    {
                        data: 'payload_html',
                        orderable: false,
                        searchable: false,
                        className: 'pa-dt-events-cell',
                        render: function (d) {
                            return d;
                        },
                    },
                ],
            };
        case 'goals': {
            const confirmMsg =
                (tableEl && tableEl.dataset.paDtConfirmDelete) || 'Delete this event?';
            const removeLabel = (tableEl && tableEl.dataset.paDtRemoveLabel) || 'Remove';
            return {
                ...base,
                order: [[0, 'asc']],
                columns: [
                    { data: 'label' },
                    { data: 'event_name', className: 'font-monospace text-muted' },
                    { data: 'count', className: 'text-end font-monospace' },
                    { data: 'unique_visitors', className: 'text-end font-monospace' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: function (data, cellType, row) {
                            return (
                                '<form method="POST" action="' +
                                escapeHtml(row.delete_url) +
                                '" class="d-inline" data-confirm="' +
                                escapeHtml(confirmMsg) +
                                '" onsubmit="return confirm(this.dataset.confirm);">' +
                                '<input type="hidden" name="_token" value="' +
                                escapeHtml(csrf) +
                                '">' +
                                '<input type="hidden" name="_method" value="DELETE">' +
                                '<button type="submit" class="btn btn-link btn-sm text-danger p-0">' +
                                escapeHtml(removeLabel) +
                                '</button></form>'
                            );
                        },
                    },
                ],
            };
        }
        default:
            return null;
    }
}

function init() {
    const tables = document.querySelectorAll('table.pa-site-dt');
    if (!tables.length) {
        return;
    }

    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    tables.forEach(function (table) {
        const url = table.dataset.paDtUrl;
        const type = table.dataset.paDtType;
        const range = table.dataset.paDtRange;
        if (!url || !type || !range) {
            return;
        }

        const cfg = tableConfig(type, csrfToken, table);
        if (!cfg) {
            return;
        }

        new DataTable(table, {
            ...cfg,
            ajax: {
                url: url,
                type: 'POST',
                data: function (d) {
                    d.range = range;
                    d.type = type;
                    if (csrfToken) {
                        d._token = csrfToken;
                    }
                    const filters = getPaAnalyticsFilterParams();
                    Object.keys(filters).forEach(function (k) {
                        d[k] = filters[k];
                    });
                    return d;
                },
            },
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
