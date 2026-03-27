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
</head>
<body id="page-top">
    <div id="wrapper">
        <div class="sidebar-backdrop d-md-none" id="sidebarBackdrop" aria-hidden="true"></div>
        <ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-arrow-trend-up"></i>
                </div>
                <div class="sidebar-brand-text">{{ config('app.name') }}</div>
            </a>
            <div class="sidebar-user-block" role="region" aria-label="{{ __('Account') }}">
                <div class="sidebar-user-label">{{ __('Nome') }}</div>
                <div class="sidebar-user-value sidebar-user-value--name text-truncate" title="{{ Auth::user()->name }}">
                    {{ Auth::user()->name }}
                </div>
                <div class="sidebar-user-label sidebar-user-label--spaced">{{ __('Email') }}</div>
                <div class="sidebar-user-value sidebar-user-value--email text-truncate" title="{{ Auth::user()->email }}">
                    {{ Auth::user()->email }}
                </div>
            </div>
            <hr class="sidebar-divider my-0">
            <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    <i class="fas fa-fw fa-gauge-high"></i>
                    <span>{{ __('Dashboard') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->routeIs('sites.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('sites.index') }}">
                    <i class="fas fa-fw fa-globe"></i>
                    <span>{{ __('Siti') }}</span>
                </a>
            </li>
            @if (Auth::user()->isAdmin())
                <li class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('users.index') }}">
                        <i class="fas fa-fw fa-users"></i>
                        <span>{{ __('users.page_title') }}</span>
                    </a>
                </li>
            @endif
            <li class="nav-item {{ request()->routeIs('preferences.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('preferences.edit') }}">
                    <i class="fas fa-fw fa-sliders"></i>
                    <span>{{ __('Impostazioni') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->routeIs('account.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('account.edit') }}">
                    <i class="fas fa-fw fa-user-circle"></i>
                    <span>{{ __('Account') }}</span>
                </a>
            </li>
            <hr class="sidebar-divider">
            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}" class="mb-0 w-100">
                    @csrf
                    <button type="submit" class="nav-link border-0 bg-transparent text-white w-100 shadow-none rounded-0">
                        <i class="fas fa-fw fa-right-from-bracket"></i>
                        <span>{{ __('Esci') }}</span>
                    </button>
                </form>
            </li>
            <hr class="sidebar-divider d-none d-md-block">
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle" type="button" aria-label="Toggle sidebar"></button>
            </div>
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="nd-mobile-topbar d-md-none border-bottom py-2 mb-2 position-relative">
                    <button
                        id="sidebarToggleTop"
                        class="btn btn-link position-absolute top-50 start-0 translate-middle-y ms-2 p-2 border-0"
                        type="button"
                        aria-label="{{ __('Menu') }}"
                    >
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                    <div class="text-center px-5">
                        <a
                            href="{{ route('dashboard') }}"
                            class="nd-mobile-topbar-brand text-decoration-none d-inline-flex align-items-center justify-content-center gap-1 py-1 min-w-0"
                        >
                            <i class="fas fa-arrow-trend-up flex-shrink-0" aria-hidden="true"></i>
                            <span class="fw-bold text-dark text-truncate">{{ config('app.name') }}</span>
                        </a>
                    </div>
                </div>

                <div class="container-fluid pa-page-main">
                    @yield('content')
                </div>
            </div>

            <footer class="sticky-footer">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>&copy; {{ config('app.name') }} {{ date('Y') }}</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    @include('partials.datatables-language')

    @stack('scripts')
</body>
</html>
