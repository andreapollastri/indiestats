function normalizeQuery(value) {
    return String(value || '')
        .trim()
        .toLocaleLowerCase();
}

function initDashboardSitesFilter() {
    const input = document.getElementById('pa-dashboard-sites-filter');
    const items = document.querySelectorAll('[data-pa-site-filter-item]');
    const empty = document.getElementById('pa-dashboard-sites-filter-empty');

    if (!input || !items.length) {
        return;
    }

    const applyFilter = () => {
        const query = normalizeQuery(input.value);
        let visible = 0;

        items.forEach((item) => {
            const name = normalizeQuery(item.getAttribute('data-pa-site-name'));
            const matches = query === '' || name.includes(query);
            item.classList.toggle('d-none', !matches);
            if (matches) {
                visible += 1;
            }
        });

        if (empty) {
            empty.classList.toggle('d-none', visible > 0);
        }
    };

    input.addEventListener('input', applyFilter);
    input.addEventListener('search', applyFilter);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardSitesFilter);
} else {
    initDashboardSitesFilter();
}
