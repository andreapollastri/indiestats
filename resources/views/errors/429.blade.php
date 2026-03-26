@extends('layouts.marketing')

@section('title', __('Error 429: page title').' · '.config('app.name'))

@section('content')
    @php
        $retrySeconds = null;
        if (isset($exception) && is_object($exception) && method_exists($exception, 'getHeaders')) {
            $headers = $exception->getHeaders();
            $retrySeconds = isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null;
        }
    @endphp

    <section class="nd-hero position-relative" style="padding: clamp(2.5rem, 8vw, 5rem) 0 4rem;">
        <div class="nd-grid-bg"></div>
        <div class="container position-relative">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7 text-center">
                    <div class="nd-float mb-4 d-inline-flex align-items-center justify-content-center position-relative"
                        style="width: 120px; height: 120px;">
                        <span class="position-absolute rounded-circle"
                            style="inset: 0; background: radial-gradient(circle at 30% 30%, rgba(16,185,129,0.15), transparent 55%);"></span>
                        <span class="position-absolute rounded-circle border"
                            style="inset: 12px; border-color: rgba(16,185,129,0.25) !important;"></span>
                        <span class="position-absolute rounded-circle border"
                            style="inset: 28px; border-color: rgba(6,182,212,0.12) !important;"></span>
                        <span class="position-relative d-flex align-items-center justify-content-center rounded-circle"
                            style="width: 64px; height: 64px; background: linear-gradient(145deg, rgba(16,185,129,0.12), rgba(6,182,212,0.08)); border: 1px solid rgba(16,185,129,0.2);">
                            <i class="fas fa-mug-hot" style="font-size: 1.5rem; color: #059669;"></i>
                        </span>
                    </div>

                    <p class="nd-stat-pill mb-3" style="background: rgba(16,185,129,0.08); color: #047857;">
                        <span style="font-family: 'JetBrains Mono', monospace; font-weight: 700;">429</span>
                        <span style="opacity: 0.6;">·</span>
                        {{ __('Too Many Requests') }}
                    </p>

                    <h1 class="fw-bold mb-3" style="font-size: clamp(1.5rem, 4vw, 2rem); color: #0f172a; letter-spacing: -0.03em; line-height: 1.2;">
                        {{ __('Error 429: headline') }}
                    </h1>

                    <p class="mb-4 mx-auto" style="color: #64748b; font-size: 1.02rem; line-height: 1.75; max-width: 520px;">
                        {{ __('Error 429: lead') }}
                    </p>

                    @if ($retrySeconds !== null && $retrySeconds > 0)
                        <p class="mb-4 small" style="font-family: 'JetBrains Mono', monospace; color: #475569;">
                            <i class="fas fa-clock me-1" style="color: #10b981; font-size: 0.85em;"></i>
                            {{ __('Error 429: retry in', ['seconds' => $retrySeconds]) }}
                        </p>
                    @endif

                    <div class="d-flex flex-wrap gap-2 justify-content-center align-items-center">
                        <button type="button" class="btn btn-primary px-4"
                            style="padding: 0.55rem 1.25rem; font-size: 0.9rem;"
                            onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = @json(route('home')); }">
                            <i class="fas fa-arrow-left me-2" style="font-size: 0.75rem;"></i>{{ __('Error 429: back') }}
                        </button>
                        <a href="{{ route('home') }}" class="btn px-4"
                            style="color: #475569; font-weight: 500; padding: 0.55rem 1.25rem; font-size: 0.9rem;">
                            {{ __('Error 429: home') }}
                        </a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4"
                                style="padding: 0.55rem 1.25rem; font-size: 0.9rem;">
                                {{ __('Dashboard') }}
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
