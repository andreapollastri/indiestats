@extends('layouts.guest')

@section('content')
    <div class="text-center mb-4">
        <h1 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.25rem;">{{ __('guest.reset_password.heading') }}</h1>
        <p class="mb-0" style="color: #94a3b8; font-size: 0.8rem;">{{ __('guest.reset_password.subtitle') }}</p>
    </div>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('guest.reset_password.email') }}</label>
            <input type="email" name="email" value="{{ old('email', $email) }}" required class="form-control @error('email') is-invalid @enderror" id="email" autocomplete="username">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('guest.reset_password.password') }}</label>
            <input type="password" name="password" required autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('guest.reset_password.password_placeholder') }}">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('guest.reset_password.confirm') }}</label>
            <input type="password" name="password_confirmation" required autocomplete="new-password" class="form-control" placeholder="{{ __('guest.reset_password.confirm_placeholder') }}">
        </div>
        <button type="submit" class="btn btn-primary w-100" style="padding: 0.5rem;">{{ __('guest.reset_password.submit') }}</button>
    </form>
@endsection
