@extends('layouts.guest')

@section('content')
    <div class="text-center">
        <h1 class="h4 text-gray-900 mb-4">{{ __('Conferma password') }}</h1>
    </div>
    <form method="POST" action="{{ route('password.confirm.store') }}" class="user">
        @csrf
        <div class="mb-3">
            <input type="password" name="password" required autocomplete="current-password" class="form-control rounded-3 @error('password') is-invalid @enderror" placeholder="{{ __('Password') }}">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100">{{ __('Conferma') }}</button>
    </form>
@endsection
