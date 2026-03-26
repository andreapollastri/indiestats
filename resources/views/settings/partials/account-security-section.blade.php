@if (! $hasVerifiedEmail)
    <div class="alert alert-warning border-0 mb-4" role="alert">
        <p class="small mb-0">{{ __('Verifica il tuo indirizzo email per poter aggiornare la password e gestire il 2FA.') }}</p>
    </div>
@endif

<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="m-0" style="color: #10b981;">{{ __('Cambia password') }}</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('user-password.update') }}">
            @csrf
            @method('PUT')
            <fieldset @if (! $hasVerifiedEmail) disabled @endif>
                <div class="mb-3">
                    <label for="current_password" class="form-label">{{ __('Password attuale') }}</label>
                    <input id="current_password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('Nuova password') }}</label>
                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">{{ __('Conferma nuova password') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary" data-test="update-password-button">{{ __('Aggiorna password') }}</button>
            </fieldset>
        </form>
    </div>
</div>

@if ($canManageTwoFactor)
    <div class="card mb-4">
        <div class="card-header py-3">
            <h6 class="m-0" style="color: #10b981;">{{ __('Autenticazione a due fattori') }}</h6>
        </div>
        <div class="card-body">
            @if (! $hasVerifiedEmail)
                <p class="small mb-0" style="color: #94a3b8;">{{ __('Verifica il tuo indirizzo email per attivare o gestire il 2FA.') }}</p>
            @elseif (! $twoFactorEnabled)
                <p class="small" style="color: #94a3b8;">{{ __("Attiva 2FA per richiedere un codice dall'app di autenticazione al login.") }}</p>
                @if ($pendingTwoFactorConfirm ?? false)
                    <div id="pa-two-factor-qr" class="mb-3 p-3 rounded text-center" style="background: #f8fafc; min-height: 200px;" data-qr-url="{{ route('two-factor.qr-code') }}"></div>
                    <p class="small mb-3">{{ __('Inserisci il codice a 6 cifre per confermare.') }}</p>
                    <div class="d-flex flex-wrap align-items-end gap-2 mb-4">
                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="d-flex flex-wrap align-items-end gap-2">
                            @csrf
                            <div>
                                <label for="code" class="visually-hidden">{{ __('Codice') }}</label>
                                <input id="code" type="text" name="code" maxlength="6" class="form-control" required autocomplete="one-time-code" placeholder="{{ __('Codice') }}" style="font-family: 'JetBrains Mono', monospace;">
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Conferma') }}</button>
                        </form>
                        <form method="POST" action="{{ route('security.two-factor.cancel-setup') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary">{{ __('Annulla') }}</button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('two-factor.enable') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">{{ __('Attiva 2FA') }}</button>
                    </form>
                @endif
            @else
                <p class="small" style="color: #94a3b8;">{{ __('Il login richiederà un codice dalla tua app di autenticazione.') }}</p>
                <div
                    id="pa-recovery-codes-wrap"
                    class="mb-3"
                    data-codes-url="{{ route('two-factor.recovery-codes') }}"
                    data-user-id="{{ Auth::id() }}"
                    data-label-copy="{{ __('Copia tutti') }}"
                    data-label-copied="{{ __('Copiato') }}"
                >
                    <p class="small fw-semibold mb-2" style="color: #64748b;">{{ __('Codici di recupero') }}</p>
                    <p id="pa-recovery-codes-hidden-hint" class="small mb-2 d-none" style="color: #94a3b8;">
                        {{ __('I codici sono salvati solo in questa schermata: sono nascosti per sicurezza. Rigenerali per visualizzarne di nuovi.') }}
                    </p>
                    <div id="pa-recovery-codes" class="small font-monospace mb-2 p-3 rounded d-none" style="background: #f8fafc; border: 1px solid #e2e8f0;"></div>
                    <div id="pa-recovery-codes-actions" class="d-none mb-3">
                        <button type="button" id="pa-recovery-codes-dismiss" class="btn btn-sm btn-outline-primary me-2">
                            {{ __('Ho salvato i codici') }}
                        </button>
                        <button type="button" id="pa-recovery-codes-copy" class="btn btn-sm btn-outline-secondary">
                            {{ __('Copia tutti') }}
                        </button>
                    </div>
                </div>
                <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}" class="mb-3" id="pa-regenerate-recovery-form">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">{{ __('Rigenera codici di recupero') }}</button>
                </form>
                <form method="POST" action="{{ route('two-factor.disable') }}" data-confirm="{{ __('Disattivare il 2FA?') }}" onsubmit="return confirm(this.dataset.confirm);">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">{{ __('Disattiva 2FA') }}</button>
                </form>
            @endif
        </div>
    </div>
@endif
