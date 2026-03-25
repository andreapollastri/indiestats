@extends('layouts.guest')

@section('content')
    <div class="text-center mb-4">
        <h1 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.25rem;">{{ __('Crea un account') }}</h1>
        <p class="mb-0" style="color: #94a3b8; font-size: 0.8rem;">{{ __('Inizia a tracciare in pochi secondi') }}</p>
    </div>
    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">{{ __('Nome') }}</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="{{ __('Il tuo nome') }}">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="form-control @error('email') is-invalid @enderror" id="email" placeholder="nome@esempio.com">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <label class="form-label">{{ __('Password') }}</label>
                <input type="password" name="password" required autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('Min. 8 caratteri') }}">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ __('Conferma') }}</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password" class="form-control" placeholder="{{ __('Ripeti password') }}">
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100" style="padding: 0.5rem;">{{ __('Crea account') }}</button>
    </form>
@endsection

@section('footer')
    <span style="color: #94a3b8;">{{ __('Hai già un account?') }}</span>
    <a href="{{ route('login') }}" style="color: #10b981; text-decoration: none; font-weight: 500;">{{ __('Accedi') }}</a>
@endsection
