@extends('layouts.guest')

@section('content')
    <div class="text-center mb-4">
        <div class="mb-3">
            <span style="background: rgba(16,185,129,0.08); color: #10b981; width: 48px; height: 48px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                <i class="fas fa-shield-halved"></i>
            </span>
        </div>
        <h1 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.25rem;">{{ __('Autenticazione 2FA') }}</h1>
        <p class="mb-0" style="color: #94a3b8; font-size: 0.8rem;" id="pa-tfa-desc">{{ __("Inserisci il codice a 6 cifre dall'app di autenticazione.") }}</p>
    </div>
    <form method="POST" action="{{ route('two-factor.login.store') }}" id="pa-tfa-form-otp">
        @csrf
        <div class="mb-4">
            <input type="text" name="code" id="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="form-control text-center @error('code') is-invalid @enderror" autocomplete="one-time-code" autofocus placeholder="000000" style="font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; letter-spacing: 0.4em; padding: 0.75rem;">
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100" style="padding: 0.5rem;">{{ __('Verifica') }}</button>
    </form>
    <form method="POST" action="{{ route('two-factor.login.store') }}" class="d-none" id="pa-tfa-form-recovery">
        @csrf
        <div class="mb-4">
            <label for="recovery_code" class="form-label">{{ __('Codice di recupero') }}</label>
            <input type="text" name="recovery_code" id="recovery_code" class="form-control @error('recovery_code') is-invalid @enderror" autocomplete="off" placeholder="{{ __('Codice di recupero') }}" style="font-family: 'JetBrains Mono', monospace;">
            @error('recovery_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100" style="padding: 0.5rem;">{{ __('Verifica') }}</button>
    </form>
    <div class="text-center mt-3">
        <button type="button" class="btn btn-link btn-sm p-0" id="pa-tfa-toggle" style="color: #10b981; text-decoration: none; font-weight: 500; font-size: 0.8rem;">{{ __('Usa un codice di recupero') }}</button>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var otpForm = document.getElementById('pa-tfa-form-otp');
    var recForm = document.getElementById('pa-tfa-form-recovery');
    var toggle = document.getElementById('pa-tfa-toggle');
    var desc = document.getElementById('pa-tfa-desc');
    if (!otpForm || !recForm || !toggle) return;
    var useRecovery = false;
    toggle.addEventListener('click', function () {
        useRecovery = !useRecovery;
        otpForm.classList.toggle('d-none', useRecovery);
        recForm.classList.toggle('d-none', !useRecovery);
        if (desc) {
            desc.textContent = useRecovery
                ? @json(__('Inserisci uno dei codici di recupero.'))
                : @json(__("Inserisci il codice a 6 cifre dall'app di autenticazione."));
        }
        toggle.textContent = useRecovery
            ? @json(__("Usa il codice dall'app"))
            : @json(__('Usa un codice di recupero'));
        if (useRecovery) {
            document.getElementById('recovery_code').focus();
        } else {
            document.getElementById('code').focus();
        }
    });
})();
</script>
@endpush
