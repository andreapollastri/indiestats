<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
    <style>
        .nd-auth-bg {
            min-height: 100vh;
            background: #fff;
            position: relative;
        }
        .nd-auth-bg::before {
            content: "";
            position: absolute; top: 0; left: 0; right: 0; height: 50%;
            background: radial-gradient(ellipse 80% 70% at 50% -10%, rgba(16,185,129,0.06) 0%, transparent 70%);
            pointer-events: none;
        }
        .nd-auth-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
        }
    </style>
</head>
<body class="nd-auth-bg">
    <div class="container position-relative" style="z-index: 1;">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
                <div class="text-center" style="margin-top: clamp(2rem, 8vh, 5rem); margin-bottom: 1.5rem;">
                    <a href="{{ route('home') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                        <span style="background: #10b981; color: #fff; width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">
                            <i class="fas fa-arrow-trend-up"></i>
                        </span>
                        <span style="font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 1rem; color: #0f172a; letter-spacing: -0.02em;">{{ config('app.name') }}</span>
                    </a>
                </div>
                <div class="nd-auth-card">
                    <div class="p-4 p-md-5">
                        @if ($errors->any())
                            <div class="alert alert-danger small mb-3">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @yield('content')
                    </div>
                </div>
                @hasSection('footer')
                    <div class="text-center mt-3 mb-4" style="font-size: 0.8rem;">@yield('footer')</div>
                @endif
            </div>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
