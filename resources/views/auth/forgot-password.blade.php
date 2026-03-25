@extends('layouts.guest')

@section('content')
    <div class="text-center">
        <h1 class="h4 text-gray-900 mb-2">{{ __('Password dimenticata') }}</h1>
        <p class="small text-muted mb-4">{{ __('Inserisci la tua email per ricevere il link di reset.') }}</p>
    </div>
    @if (!empty($status))
        <div class="alert alert-success small">{{ $status }}</div>
    @endif
    <form method="POST" action="{{ route('password.email') }}" class="user">
        @csrf
        <div class="mb-3">
            <input type="email" name="email" value="{{ old('email') }}" required autofocus class="form-control rounded-3 @error('email') is-invalid @enderror" placeholder="{{ __('Indirizzo email') }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100">{{ __('Invia link') }}</button>
    </form>
    <hr>
@endsection

@section('footer')
    <a class="small text-white-50" href="{{ route('login') }}">{{ __('Torna al login') }}</a>
@endsection
