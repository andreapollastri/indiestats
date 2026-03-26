@extends('layouts.guest')

@section('content')
    <div class="text-center mb-4">
        <div class="mb-3">
            <span style="background: rgba(16,185,129,0.08); color: #10b981; width: 48px; height: 48px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                <i class="fas fa-key"></i>
            </span>
        </div>
        <h1 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.25rem;">{{ __('Password dimenticata') }}</h1>
        <p class="mb-0" style="color: #94a3b8; font-size: 0.8rem;">{{ __('Inserisci la tua email per ricevere il link di reset.') }}</p>
    </div>
    @if (!empty($status))
        <div class="alert alert-success small">{{ __($status) }}</div>
    @endif
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-4">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus class="form-control @error('email') is-invalid @enderror" placeholder="nome@esempio.com">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100" style="padding: 0.5rem;">{{ __('Invia link di reset') }}</button>
    </form>
@endsection

@section('footer')
    <a href="{{ route('login') }}" style="color: #64748b; text-decoration: none; font-size: 0.8rem;">
        <i class="fas fa-arrow-left me-1" style="font-size: 0.65rem;"></i>{{ __('Torna al login') }}
    </a>
@endsection
