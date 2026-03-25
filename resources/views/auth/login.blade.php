@extends('layouts.guest')

@section('content')
    <div class="text-center">
        <h1 class="h4 text-gray-900 mb-4">{{ __('Accedi') }}</h1>
    </div>
    @if (!empty($status))
        <div class="alert alert-success small">{{ $status }}</div>
    @endif
    <form method="POST" action="{{ route('login.store') }}" class="user">
        @csrf
        <div class="mb-3">
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-control rounded-3 @error('email') is-invalid @enderror" id="email" placeholder="{{ __('Indirizzo email') }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <input type="password" name="password" required autocomplete="current-password" class="form-control rounded-3 @error('password') is-invalid @enderror" id="password" placeholder="{{ __('Password') }}">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <div class="form-check small">
                <input type="checkbox" class="form-check-input" name="remember" id="remember" value="1">
                <label class="form-check-label" for="remember">{{ __('Ricordami') }}</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100" data-test="login-button">{{ __('Accedi') }}</button>
    </form>
    <hr>
    <div class="text-center">
        @if ($canResetPassword)
            <a class="small" href="{{ route('password.request') }}">{{ __('Password dimenticata?') }}</a>
        @endif
    </div>
@endsection

@section('footer')
    @if ($canRegister)
        <a class="small text-white-50" href="{{ route('register') }}">{{ __('Crea un account') }}</a>
    @endif
@endsection
