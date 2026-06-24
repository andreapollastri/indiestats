function initDetailJumpNav() {
    const nav = document.querySelector('.pa-detail-jump-nav');
    if (!nav) {
        return;
    }

    nav.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-pa-jump-section]');
        if (!btn) {
            return;
        }

        const sectionId = btn.dataset.paJumpSection;
        const accordion = document.getElementById('pa-stats-section-' + sectionId);
        if (!accordion) {
            return;
        }

        const collapse = accordion.querySelector('.accordion-collapse');
        const toggle = accordion.querySelector('.accordion-button');
        if (collapse && toggle && collapse.classList.contains('collapse') && !collapse.classList.contains('show')) {
            if (window.bootstrap && window.bootstrap.Collapse) {
                window.bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
            } else {
                collapse.classList.add('show');
                toggle.classList.remove('collapsed');
                toggle.setAttribute('aria-expanded', 'true');
            }
        }

        accordion.scrollIntoView({ behavior: 'smooth', block: 'start' });

        nav.querySelectorAll('.pa-detail-jump-nav__item').forEach(function (el) {
            el.classList.toggle('pa-detail-jump-nav__item--active', el === btn);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDetailJumpNav);
} else {
    initDetailJumpNav();
}
