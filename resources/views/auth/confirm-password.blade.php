@extends('layouts.guest')

@section('content')
    <div class="text-center mb-4">
        <div class="mb-3">
            <span style="background: rgba(16,185,129,0.08); color: #10b981; width: 48px; height: 48px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                <i class="fas fa-lock"></i>
            </span>
        </div>
        <h1 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.25rem;">{{ __('guest.confirm_password.heading') }}</h1>
        <p class="mb-0" style="color: #94a3b8; font-size: 0.8rem;">{{ __('guest.confirm_password.subtitle') }}</p>
    </div>
    <form method="POST" action="{{ route('password.confirm.store') }}">
        @csrf
        <div class="mb-4">
            <label for="password" class="form-label">{{ __('guest.confirm_password.password') }}</label>
            <input type="password" name="password" required autocomplete="current-password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="{{ __('guest.confirm_password.password_placeholder') }}">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100" style="padding: 0.5rem;">{{ __('guest.confirm_password.submit') }}</button>
    </form>
@endsection
