@push('scripts')
    <script>
        (function () {
            const role = document.getElementById('role');
            const block = document.getElementById('pa-user-sites-assign');
            const note = document.getElementById('pa-user-sites-admin-note');
            if (!role || !block || !note) {
                return;
            }
            function sync() {
                const isAdmin = role.value === 'admin';
                block.classList.toggle('d-none', isAdmin);
                note.classList.toggle('d-none', !isAdmin);
            }
            role.addEventListener('change', sync);
        })();
    </script>
@endpush
