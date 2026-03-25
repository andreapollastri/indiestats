<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-gradient-primary">
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent border-0">
        <div class="container">
            <a class="navbar-brand fw-bold text-white" href="{{ route('home') }}">{{ config('app.name') }}</a>
            <div class="ms-auto d-flex align-items-center">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm shadow-sm">{{ __('Dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm me-2">{{ __('Accedi') }}</a>
                    @if ($canRegister ?? true)
                        <a href="{{ route('register') }}" class="btn btn-light btn-sm shadow-sm">{{ __('Registrati') }}</a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>
    <main class="pb-5">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
