@extends('layouts.guest')

@section('content')
    <div class="text-center">
        <h1 class="h4 text-gray-900 mb-4">{{ __('Crea un account') }}</h1>
    </div>
    <form method="POST" action="{{ route('register.store') }}" class="user">
        @csrf
        <div class="mb-3">
            <input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="form-control rounded-3 @error('name') is-invalid @enderror" placeholder="{{ __('Nome') }}">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="form-control rounded-3 @error('email') is-invalid @enderror" placeholder="{{ __('Indirizzo email') }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <input type="password" name="password" required autocomplete="new-password" class="form-control rounded-3 @error('password') is-invalid @enderror" placeholder="{{ __('Password') }}">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6">
                <input type="password" name="password_confirmation" required autocomplete="new-password" class="form-control rounded-3" placeholder="{{ __('Ripeti password') }}">
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">{{ __('Registrati') }}</button>
    </form>
    <hr>
@endsection

@section('footer')
    <a class="small text-white-50" href="{{ route('login') }}">{{ __('Hai già un account? Accedi') }}</a>
@endsection
