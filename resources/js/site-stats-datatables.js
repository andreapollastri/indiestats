import DataTable from 'datatables.net-responsive-bs5';
import { getPaAnalyticsFilterParams } from './site-filters.js';

/** Traduzioni inline (evita errori TN/21 da caricamento i18n via CDN/CORS). */
const DT_IT = {
    emptyTable: 'Nessun dato disponibile nella tabella',
    info: 'Visualizzazione da _START_ a _END_ di _TOTAL_ elementi',
    infoEmpty: 'Visualizzazione da 0 a 0 di 0 elementi',
    infoFiltered: '(filtrati da _MAX_ elementi totali)',
    infoThousands: '.',
    decimal: ',',
    lengthMenu: 'Visualizza _MENU_ elementi',
    loadingRecords: 'Caricamento...',
    processing: 'Elaborazione...',
    search: 'Cerca:',
    zeroRecords: 'Nessun elemento corrispondente trovato',
    paginate: {
        first: 'Inizio',
        last: 'Fine',
        next: 'Succ.',
        previous: 'Prec.',
    },
    aria: {
        orderable: 'Ordinabile',
        orderableReverse: 'Ordinabile (ordine inverso)',
        orderableRemove: 'Ordinabile (rimuovi ordinamento)',
        sortAscending: ': attiva per ordinare la colonna in ordine crescente',
        sortDescending: ': attiva per ordinare la colonna in ordine decrescente',
    },
};

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
        language: DT_IT,
    };

    switch (type) {
        case 'paths':
            return {
                ...base,
                columns: num('path', false),
            };
        case 'utm':
            return {
                ...base,
                columns: num('utm_source', true),
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
                columns: [
                    { data: 'created_at', className: 'text-nowrap font-monospace' },
                    { data: 'name', className: 'font-monospace' },
                    {
                        data: 'path',
                        className: 'font-monospace',
                        render: function (d) {
                            return escapeHtml(d || '—');
                        },
                    },
                    {
                        data: 'referrer_display',
                        orderable: true,
                        render: function (d) {
                            return d;
                        },
                    },
                    {
                        data: 'payload_html',
                        orderable: false,
                        searchable: false,
                        render: function (d) {
                            return d;
                        },
                    },
                ],
            };
        case 'goals': {
            const confirmMsg =
                (tableEl && tableEl.dataset.paDtConfirmDelete) || 'Eliminare questo goal?';
            const removeLabel = (tableEl && tableEl.dataset.paDtRemoveLabel) || 'Rimuovi';
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

    const csrf =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    tables.forEach(function (table) {
        const url = table.dataset.paDtUrl;
        const type = table.dataset.paDtType;
        const range = table.dataset.paDtRange;
        if (!url || !type || !range) {
            return;
        }

        const cfg = tableConfig(type, csrf, table);
        if (!cfg) {
            return;
        }

        new DataTable(table, {
            ...cfg,
            ajax: {
                url: url,
                data: function (d) {
                    d.range = range;
                    d.type = type;
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
