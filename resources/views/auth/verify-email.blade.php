@extends('layouts.guest')

@section('content')
    <div class="text-center mb-4">
        <div class="mb-3">
            <span style="background: rgba(16,185,129,0.08); color: #10b981; width: 48px; height: 48px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                <i class="fas fa-envelope"></i>
            </span>
        </div>
        <h1 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.25rem;">{{ __('guest.verify_email.heading') }}</h1>
        <p class="mb-0" style="color: #94a3b8; font-size: 0.8rem;">{{ __('guest.verify_email.subtitle') }}</p>
    </div>
    @if (!empty($status))
        <div class="alert alert-success small">{{ __($status) }}</div>
    @endif
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary w-100" style="padding: 0.5rem;">{{ __('guest.verify_email.resend') }}</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
        @csrf
        <button type="submit" class="btn btn-link btn-sm p-0" style="color: #94a3b8; text-decoration: none; font-size: 0.8rem;">{{ __('guest.verify_email.logout') }}</button>
    </form>
@endsection
