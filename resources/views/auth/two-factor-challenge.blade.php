@extends('layouts.guest')

@section('content')
    <div class="text-center">
        <h1 class="h4 text-gray-900 mb-2">{{ __('Codice di autenticazione') }}</h1>
        <p class="small text-muted mb-4" id="pa-tfa-desc">{{ __('Inserisci il codice a 6 cifre dall’app di autenticazione.') }}</p>
    </div>
    <form method="POST" action="{{ route('two-factor.login.store') }}" class="user" id="pa-tfa-form-otp">
        @csrf
        <div class="mb-3">
            <input type="text" name="code" id="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="form-control rounded-3 text-center @error('code') is-invalid @enderror" autocomplete="one-time-code" autofocus placeholder="000000">
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100">{{ __('Continua') }}</button>
    </form>
    <form method="POST" action="{{ route('two-factor.login.store') }}" class="user d-none" id="pa-tfa-form-recovery">
        @csrf
        <div class="mb-3">
            <input type="text" name="recovery_code" id="recovery_code" class="form-control rounded-3 @error('recovery_code') is-invalid @enderror" autocomplete="off" placeholder="{{ __('Codice di recupero') }}">
            @error('recovery_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100">{{ __('Continua') }}</button>
    </form>
    <div class="text-center mt-3">
        <button type="button" class="btn btn-link btn-sm" id="pa-tfa-toggle">{{ __('Usa un codice di recupero') }}</button>
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
                : @json(__('Inserisci il codice a 6 cifre dall’app di autenticazione.'));
        }
        toggle.textContent = useRecovery
            ? @json(__('Usa il codice dall’app'))
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
