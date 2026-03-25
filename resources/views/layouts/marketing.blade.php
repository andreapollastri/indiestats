<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
    <style>
        .nd-public { --nd-accent: #10b981; --nd-dark: #0f172a; }
        .nd-public body, .nd-public { font-family: "Inter", system-ui, -apple-system, sans-serif; }
        .nd-hero { position: relative; overflow: hidden; }
        .nd-hero::before {
            content: "";
            position: absolute; inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 50% -20%, rgba(16,185,129,0.08) 0%, transparent 70%),
                radial-gradient(ellipse 60% 50% at 80% 50%, rgba(6,182,212,0.05) 0%, transparent 60%);
            pointer-events: none;
        }
        .nd-nav { background: rgba(255,255,255,0.8); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid #f1f5f9; }
        .nd-grid-bg {
            position: absolute; inset: 0; pointer-events: none; opacity: 0.4;
            background-image:
                linear-gradient(rgba(16,185,129,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(16,185,129,0.05) 1px, transparent 1px);
            background-size: 64px 64px;
            mask-image: radial-gradient(ellipse 70% 50% at 50% 30%, black 20%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse 70% 50% at 50% 30%, black 20%, transparent 70%);
        }
        .nd-feature-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0;
        }
        .nd-stat-pill {
            display: inline-flex; align-items: center; gap: 0.375rem;
            padding: 0.375rem 0.75rem; border-radius: 2rem;
            font-family: "JetBrains Mono", monospace; font-size: 0.7rem; font-weight: 500;
        }
        @keyframes nd-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
        .nd-float { animation: nd-float 4s ease-in-out infinite; }
        .nd-float-delay { animation: nd-float 4s ease-in-out 1s infinite; }
    </style>
</head>
<body class="nd-public" style="background: #ffffff; min-height: 100vh;">
    <nav class="nd-nav sticky-top" style="z-index: 100;">
        <div class="container d-flex align-items-center" style="height: 56px;">
            <a class="text-decoration-none d-flex align-items-center gap-2" href="{{ route('home') }}">
                <span style="background: #10b981; color: #fff; width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                    <i class="fas fa-arrow-trend-up"></i>
                </span>
                <span style="font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 0.9rem; color: #0f172a; letter-spacing: -0.02em;">{{ config('app.name') }}</span>
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">{{ __('Dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm" style="color: #475569; font-weight: 500;">{{ __('Accedi') }}</a>
                    @if ($canRegister ?? true)
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm">{{ __('Registrati') }}</a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>
    <main>
        @yield('content')
    </main>
    <footer style="border-top: 1px solid #f1f5f9; padding: 2rem 0;">
        <div class="container text-center">
            <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.7rem; color: #94a3b8;">&copy; {{ config('app.name') }} {{ date('Y') }}</span>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
