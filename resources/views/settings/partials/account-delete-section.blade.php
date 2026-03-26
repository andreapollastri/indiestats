<div class="card mb-4 border-left-danger">
    <div class="card-body">
        <h6 class="fw-bold mb-2" style="color: #ef4444;">{{ __('Elimina account') }}</h6>
        <p class="small mb-3" style="color: #94a3b8;">{{ __('Questa azione è irreversibile.') }}</p>
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAccountModal" data-test="delete-user-button">{{ __('Elimina account') }}</button>
    </div>
</div>

<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAccountModalLabel">{{ __('Conferma eliminazione') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Chiudi') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="small">{{ __("Inserisci la password per eliminare definitivamente l'account.") }}</p>
                    <div class="mb-3">
                        <label for="delete-password" class="form-label">{{ __('Password') }}</label>
                        <input id="delete-password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Annulla') }}</button>
                    <button type="submit" class="btn btn-danger" data-test="confirm-delete-user-button">{{ __('Elimina definitivamente') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
