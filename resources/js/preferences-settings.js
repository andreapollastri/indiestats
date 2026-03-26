import TomSelect from 'tom-select';

(function initPreferencesTimezoneSelect() {
    const el = document.getElementById('timezone');
    if (! el) {
        return;
    }

    const placeholder = el.dataset.placeholder || '';

    new TomSelect(el, {
        placeholder,
        maxOptions: null,
        sortField: { field: 'text', direction: 'asc' },
    });
})();
