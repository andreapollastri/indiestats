@extends('layouts.marketing')

@section('content')
    <div class="container py-5">
        <div class="row align-items-center py-lg-5">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1 class="display-4 text-white fw-bold mb-3">{{ config('app.name') }}</h1>
                <p class="lead text-white-50 mb-4">
                    Analytics leggero per i tuoi siti: snippet unico, dashboard chiara, obiettivi sugli eventi.
                    Interfaccia basata su Bootstrap 5 e tema ispirato a <a href="https://startbootstrap.com/theme/sb-admin-2" class="text-white fw-bold border-bottom border-white" target="_blank" rel="noopener">SB Admin</a> (Start Bootstrap).
                </p>
                @guest
                    <div class="d-flex flex-wrap">
                        <a href="{{ route('login') }}" class="btn btn-light shadow-sm me-2 mb-2">{{ __('Accedi') }}</a>
                        @if ($canRegister ?? true)
                            <a href="{{ route('register') }}" class="btn btn-outline-light mb-2">{{ __('Registrati') }}</a>
                        @endif
                    </div>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-light shadow-sm">{{ __('Vai alla dashboard') }}</a>
                @endguest
            </div>
            <div class="col-lg-6">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-gray-800 mb-3"><i class="fas fa-gauge-high text-primary me-2"></i>{{ __('Cosa puoi fare') }}</h2>
                        <ul class="list-unstyled mb-0 small text-muted">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>{{ __('Aggiungi siti e incolla lo snippet') }}</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>{{ __('Vedi visitatori, pagine, sorgenti e paesi') }}</li>
                            <li class="mb-0"><i class="fas fa-check text-success me-2"></i>{{ __('Definisci goal sugli eventi track') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
