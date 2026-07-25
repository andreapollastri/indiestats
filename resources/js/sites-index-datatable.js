import DataTable from 'datatables.net-responsive-bs5';

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

function readSitesIndexConfig() {
    const el = document.getElementById('pa-sites-index-config');
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

function init() {
    const table = document.getElementById('pa-sites-index-table');
    const config = readSitesIndexConfig();
    if (!table || !config || !Array.isArray(config.sites)) {
        return;
    }

    const dtLang = readDatatablesLanguage() || DEFAULT_DATATABLES_LANGUAGE;
    const labels = config.labels || {};
    const canManage = Boolean(config.canManageSites);

    const dt = new DataTable(table, {
        data: config.sites,
        responsive: true,
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100],
        ],
        language: dtLang,
        layout: {
            topStart: 'pageLength',
            topEnd: null,
            bottomStart: 'info',
            bottomEnd: 'paging',
        },
        columnDefs: [
            { responsivePriority: 1, targets: 0 },
            { responsivePriority: 2, targets: -1 },
            { orderable: false, searchable: false, targets: -1 },
            { type: 'string', targets: 0 },
        ],
        columns: [
            {
                data: 'name',
                className: 'fw-semibold',
                render: function (data, cellType, row) {
                    if (cellType !== 'display') {
                        return data || '';
                    }
                    const href = row.show_url || '#';
                    return (
                        '<a href="' +
                        escapeHtml(href) +
                        '" class="text-decoration-none text-gray-900">' +
                        escapeHtml(data || '') +
                        '</a>'
                    );
                },
            },
            {
                data: 'allowed_domains',
                render: function (data, cellType) {
                    if (cellType !== 'display') {
                        return data || '';
                    }
                    return (
                        '<span class="text-break">' + escapeHtml(data || '—') + '</span>'
                    );
                },
            },
            {
                data: 'created_at',
                className: 'text-nowrap font-monospace',
                render: function (data, cellType, row) {
                    if (cellType !== 'display') {
                        return data || '';
                    }
                    return escapeHtml(row.created_at_label || data || '—');
                },
            },
            {
                data: null,
                className: 'text-end text-nowrap',
                render: function (data, cellType, row) {
                    if (cellType !== 'display') {
                        return '';
                    }
                    const statsLabel = labels.stats || 'Stats';
                    const copyDone = labels.copyDone || 'Copied';
                    const editLabel = labels.edit || 'Edit';
                    const deleteLabel = labels.delete || 'Delete';
                    let html =
                        '<div class="d-inline-flex flex-wrap justify-content-end gap-1">' +
                        '<a href="' +
                        escapeHtml(row.show_url || '#') +
                        '" class="btn btn-outline-primary btn-sm" title="' +
                        escapeHtml(statsLabel) +
                        '" aria-label="' +
                        escapeHtml(statsLabel) +
                        '"><i class="fas fa-eye"></i></a>' +
                        '<button type="button" class="btn btn-outline-secondary btn-sm" data-copy="' +
                        escapeHtml(row.embed_code || '') +
                        '" data-copy-done="' +
                        escapeHtml(copyDone) +
                        '" title="' +
                        escapeHtml(labels.copy || 'Copy') +
                        '"><i class="fas fa-copy"></i></button>';

                    if (canManage && row.update_url) {
                        html +=
                            '<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editSiteModal" data-update-url="' +
                            escapeHtml(row.update_url) +
                            '" data-site-name="' +
                            escapeHtml(row.name || '') +
                            '" data-site-domains="' +
                            escapeHtml(row.allowed_domains || '') +
                            '" title="' +
                            escapeHtml(editLabel) +
                            '" aria-label="' +
                            escapeHtml(editLabel) +
                            '"><i class="fas fa-pen"></i></button>';
                    }

                    if (canManage && row.destroy_url) {
                        html +=
                            '<button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteSiteModal" data-delete-url="' +
                            escapeHtml(row.destroy_url) +
                            '" data-site-name="' +
                            escapeHtml(row.name || '') +
                            '" title="' +
                            escapeHtml(deleteLabel) +
                            '" aria-label="' +
                            escapeHtml(deleteLabel) +
                            '"><i class="fas fa-trash"></i></button>';
                    }

                    html += '</div>';
                    return html;
                },
            },
        ],
    });

    const filterInput = document.getElementById('pa-sites-index-filter');
    if (filterInput) {
        const applyFilter = () => {
            dt.search(filterInput.value).draw();
        };
        filterInput.addEventListener('input', applyFilter);
        filterInput.addEventListener('search', applyFilter);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
