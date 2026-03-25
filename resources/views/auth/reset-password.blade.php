@extends('layouts.guest')

@section('content')
    <div class="text-center">
        <h1 class="h4 text-gray-900 mb-4">{{ __('Nuova password') }}</h1>
    </div>
    <form method="POST" action="{{ route('password.update') }}" class="user">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3">
            <input type="email" name="email" value="{{ old('email', $email) }}" required autofocus autocomplete="username" class="form-control rounded-3 @error('email') is-invalid @enderror" placeholder="{{ __('Indirizzo email') }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <input type="password" name="password" required autocomplete="new-password" class="form-control rounded-3 @error('password') is-invalid @enderror" placeholder="{{ __('Password') }}">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6">
                <input type="password" name="password_confirmation" required autocomplete="new-password" class="form-control rounded-3" placeholder="{{ __('Conferma') }}">
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">{{ __('Reimposta password') }}</button>
    </form>
@endsection
