<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="m-0" style="color: #10b981;">{{ __('Informazioni profilo') }}</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')
            <div class="mb-3">
                <label for="name" class="form-label">{{ __('Nome') }}</label>
                <input id="name" type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required class="form-control @error('name') is-invalid @enderror" autocomplete="name">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required class="form-control @error('email') is-invalid @enderror" autocomplete="username">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary" data-test="update-profile-button">{{ __('Salva') }}</button>
        </form>
        @if ($mustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
            <hr style="border-color: #f1f5f9;">
            <p class="small text-warning mb-2">{{ __('Il tuo indirizzo email non è verificato.') }}</p>
            <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">{{ __('Invia di nuovo la mail di verifica') }}</button>
            </form>
            @if ($status === 'verification-link-sent')
                <p class="small mt-2 mb-0" style="color: #10b981;">{{ __('Nuovo link inviato.') }}</p>
            @endif
        @endif
    </div>
</div>
