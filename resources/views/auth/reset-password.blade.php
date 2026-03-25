@extends('layouts.guest')

@section('content')
    <div class="text-center mb-4">
        <h1 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.25rem;">{{ __('Nuova password') }}</h1>
        <p class="mb-0" style="color: #94a3b8; font-size: 0.8rem;">{{ __('Scegli una nuova password per il tuo account.') }}</p>
    </div>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email', $email) }}" required autofocus autocomplete="username" class="form-control @error('email') is-invalid @enderror" placeholder="nome@esempio.com">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <label class="form-label">{{ __('Password') }}</label>
                <input type="password" name="password" required autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('Nuova password') }}">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ __('Conferma') }}</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password" class="form-control" placeholder="{{ __('Conferma') }}">
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100" style="padding: 0.5rem;">{{ __('Reimposta password') }}</button>
    </form>
@endsection
