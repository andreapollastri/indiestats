@extends('layouts.guest')

@section('content')
    <div class="text-center mb-4">
        <h1 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.25rem;">{{ __('guest.login.heading') }}</h1>
        <p class="mb-0" style="color: #94a3b8; font-size: 0.8rem;">{{ __('guest.login.subtitle') }}</p>
    </div>
    @if (!empty($status))
        <div class="alert alert-success small">{{ __($status) }}</div>
    @endif
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('guest.login.email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-control @error('email') is-invalid @enderror" id="email" placeholder="{{ __('guest.login.email_placeholder') }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label mb-0">{{ __('guest.login.password') }}</label>
                @if ($canResetPassword)
                    <a href="{{ route('password.request') }}" style="font-size: 0.7rem; color: #10b981; text-decoration: none; font-weight: 500;">{{ __('guest.login.forgot_password') }}</a>
                @endif
            </div>
            <input type="password" name="password" required autocomplete="current-password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="{{ __('guest.login.password') }}">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="remember" id="remember" value="1">
                <label class="form-check-label" for="remember" style="font-size: 0.8rem; color: #64748b;">{{ __('guest.login.remember') }}</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100" data-test="login-button" style="padding: 0.5rem;">{{ __('guest.login.submit') }}</button>
    </form>
@endsection

@section('footer')
@endsection
