@extends('layouts.guest')

@section('content')
    <div class="text-center mb-4">
        <h1 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.25rem;">{{ __('Bentornato') }}</h1>
        <p class="mb-0" style="color: #94a3b8; font-size: 0.8rem;">{{ __('Accedi al tuo account') }}</p>
    </div>
    @if (!empty($status))
        <div class="alert alert-success small">{{ $status }}</div>
    @endif
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-control @error('email') is-invalid @enderror" id="email" placeholder="nome@esempio.com">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label mb-0">{{ __('Password') }}</label>
                @if ($canResetPassword)
                    <a href="{{ route('password.request') }}" style="font-size: 0.7rem; color: #10b981; text-decoration: none; font-weight: 500;">{{ __('Dimenticata?') }}</a>
                @endif
            </div>
            <input type="password" name="password" required autocomplete="current-password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="{{ __('Password') }}">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="remember" id="remember" value="1">
                <label class="form-check-label" for="remember" style="font-size: 0.8rem; color: #64748b;">{{ __('Ricordami') }}</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100" data-test="login-button" style="padding: 0.5rem;">{{ __('Accedi') }}</button>
    </form>
@endsection

@section('footer')
    @if ($canRegister)
        <span style="color: #94a3b8;">{{ __('Non hai un account?') }}</span>
        <a href="{{ route('register') }}" style="color: #10b981; text-decoration: none; font-weight: 500;">{{ __('Registrati') }}</a>
    @endif
@endsection
