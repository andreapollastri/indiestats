<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    @if ($errors->any())
                                        <div class="alert alert-danger small">
                                            <ul class="mb-0 ps-3">
                                                @foreach ($errors->all() as $e)
                                                    <li>{{ $e }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    @if (session('status'))
                                        <div class="alert alert-success small">{{ session('status') }}</div>
                                    @endif
                                    @yield('content')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @hasSection('footer')
                    <div class="text-center text-white small mb-5">@yield('footer')</div>
                @endif
            </div>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
