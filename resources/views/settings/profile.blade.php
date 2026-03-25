@extends('layouts.app')

@section('content')
    <div class="mb-4 mt-3">
        <h1 class="h3 mb-0 fw-bold" style="color: #0f172a; letter-spacing: -0.02em;">{{ __('Profilo') }}</h1>
    </div>

    @include('partials.flash')

    <div class="card mb-4">
        <div class="card-header py-3">
            <h6 class="m-0" style="color: #10b981;">{{ __('Informazioni profilo') }}</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')
                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Nome') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required class="form-control @error('name') is-invalid @enderror" autocomplete="name">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required class="form-control @error('email') is-invalid @enderror" autocomplete="username">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary" data-test="update-profile-button">{{ __('Salva') }}</button>
            </form>
            @if ($mustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <hr style="border-color: #f1f5f9;">
                <p class="small text-warning mb-2">{{ __('Il tuo indirizzo email non è verificato.') }}</p>
                <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">{{ __('Invia di nuovo la mail di verifica') }}</button>
                </form>
                @if ($status === 'verification-link-sent')
                    <p class="small mt-2 mb-0" style="color: #10b981;">{{ __('Nuovo link inviato.') }}</p>
                @endif
            @endif
        </div>
    </div>

    <div class="card mb-4 border-left-danger">
        <div class="card-body">
            <h6 class="fw-bold mb-2" style="color: #ef4444;">{{ __('Elimina account') }}</h6>
            <p class="small mb-3" style="color: #94a3b8;">{{ __('Questa azione è irreversibile.') }}</p>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAccountModal" data-test="delete-user-button">{{ __('Elimina account') }}</button>
        </div>
    </div>

    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteAccountModalLabel">{{ __('Conferma eliminazione') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Chiudi') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small">{{ __("Inserisci la password per eliminare definitivamente l'account.") }}</p>
                        <div class="mb-3">
                            <label for="delete-password" class="form-label">{{ __('Password') }}</label>
                            <input id="delete-password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Annulla') }}</button>
                        <button type="submit" class="btn btn-danger" data-test="confirm-delete-user-button">{{ __('Elimina definitivamente') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
