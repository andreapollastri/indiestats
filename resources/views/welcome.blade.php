@extends('layouts.marketing')

@section('content')
    {{-- HERO --}}
    <section class="nd-hero" style="padding: 5rem 0 4rem;">
        <div class="nd-grid-bg"></div>
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="nd-stat-pill mb-3" style="background: rgba(16,185,129,0.08); color: #059669;">
                        <i class="fas fa-bolt" style="font-size: 0.6rem;"></i>
                        Lightweight &amp; privacy-friendly
                    </div>
                    <h1 class="fw-bold mb-3" style="font-size: clamp(2rem, 5vw, 3rem); color: #0f172a; letter-spacing: -0.03em; line-height: 1.1;">
                        {{ __('Analytics semplice') }}<br>
                        <span style="color: #10b981;">{{ __('per chi crea.') }}</span>
                    </h1>
                    <p class="mb-4" style="color: #64748b; font-size: 1.05rem; line-height: 1.7; max-width: 480px;">
                        {{ __('Uno snippet, una dashboard. Monitora visitatori, pagine, sorgenti e obiettivi senza complessità.') }}
                    </p>
                    @guest
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <a href="{{ route('register') }}" class="btn btn-primary" style="padding: 0.6rem 1.5rem; font-size: 0.875rem;">
                                {{ __('Inizia gratis') }} <i class="fas fa-arrow-right ms-1" style="font-size: 0.75rem;"></i>
                            </a>
                            <a href="{{ route('login') }}" class="btn" style="color: #475569; font-weight: 500; padding: 0.6rem 1.25rem; font-size: 0.875rem;">
                                {{ __('Accedi') }}
                            </a>
                        </div>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn btn-primary" style="padding: 0.6rem 1.5rem; font-size: 0.875rem;">
                            {{ __('Vai alla dashboard') }} <i class="fas fa-arrow-right ms-1" style="font-size: 0.75rem;"></i>
                        </a>
                    @endguest
                </div>
                <div class="col-lg-5 offset-lg-1">
                    {{-- Dashboard preview card --}}
                    <div class="nd-float" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.25rem; box-shadow: 0 8px 32px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04);">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.7rem; font-weight: 600; color: #0f172a;">miosito.it</span>
                            <span class="nd-stat-pill" style="background: rgba(16,185,129,0.08); color: #059669; font-size: 0.65rem;">
                                <span style="width: 5px; height: 5px; border-radius: 50%; background: #10b981;"></span>
                                live
                            </span>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div style="background: #f8fafc; border-radius: 0.5rem; padding: 0.625rem 0.75rem;">
                                    <div style="font-size: 0.6rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500;">{{ __('Visitatori') }}</div>
                                    <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.25rem; font-weight: 700; color: #0f172a;">2,847</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div style="background: #f8fafc; border-radius: 0.5rem; padding: 0.625rem 0.75rem;">
                                    <div style="font-size: 0.6rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500;">{{ __('Pagine viste') }}</div>
                                    <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.25rem; font-weight: 700; color: #0f172a;">8,102</div>
                                </div>
                            </div>
                        </div>
                        {{-- Fake sparkline --}}
                        <div style="height: 48px; display: flex; align-items: flex-end; gap: 3px; padding: 0 2px;">
                            @php $bars = [30, 45, 35, 55, 65, 50, 70, 85, 60, 75, 90, 80, 95, 70, 85]; @endphp
                            @foreach ($bars as $h)
                                <div style="flex: 1; height: {{ $h }}%; background: {{ $loop->last ? '#10b981' : 'rgba(16,185,129,0.15)' }}; border-radius: 3px; transition: height 0.3s;"></div>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-between mt-2" style="font-family: 'JetBrains Mono', monospace; font-size: 0.6rem; color: #cbd5e1;">
                            <span>Mar 10</span>
                            <span>Mar 25</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FEATURES --}}
    <section style="padding: 4rem 0; background: #f8fafc;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-2" style="color: #0f172a; font-size: 1.75rem; letter-spacing: -0.02em;">{{ __('Tutto il necessario, niente di più.') }}</h2>
                <p style="color: #64748b; max-width: 480px; margin: 0 auto;">{{ __('Pensato per sviluppatori e maker che vogliono dati chiari senza setup complicati.') }}</p>
            </div>
            <div class="row g-4">
                @php
                    $features = [
                        ['icon' => 'fa-code', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.08)', 'title' => __('Snippet unico'), 'desc' => __('Copia e incolla una riga di JavaScript. Nessun SDK, nessuna config.')],
                        ['icon' => 'fa-chart-bar', 'color' => '#06b6d4', 'bg' => 'rgba(6,182,212,0.08)', 'title' => __('Dashboard chiara'), 'desc' => __('Visitatori, pagine, sorgenti, paesi e dispositivi in una vista sola.')],
                        ['icon' => 'fa-bullseye', 'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.08)', 'title' => __('Eventi personalizzati'), 'desc' => __('Registra tag con downstage.track() e monitora descrizione, volume e dettaglio.')],
                        ['icon' => 'fa-filter', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.08)', 'title' => __('Filtri avanzati'), 'desc' => __('Filtra per sorgente, percorso, UTM, dispositivo o paese in tempo reale.')],
                        ['icon' => 'fa-shield-halved', 'color' => '#ec4899', 'bg' => 'rgba(236,72,153,0.08)', 'title' => __('Privacy-first'), 'desc' => __('Nessun cookie di terze parti. Nessun dato personale raccolto.')],
                        ['icon' => 'fa-bolt', 'color' => '#0f172a', 'bg' => 'rgba(15,23,42,0.06)', 'title' => __('Leggerissimo'), 'desc' => __('Lo script pesa meno di 1KB. Zero impatto sulle performance.')],
                    ];
                @endphp
                @foreach ($features as $f)
                    <div class="col-md-6 col-lg-4">
                        <div class="d-flex gap-3 align-items-start" style="padding: 1.25rem; background: #fff; border: 1px solid #f1f5f9; border-radius: 0.75rem; height: 100%;">
                            <div class="nd-feature-icon" style="background: {{ $f['bg'] }}; color: {{ $f['color'] }};">
                                <i class="fas {{ $f['icon'] }}"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 0.9rem; font-weight: 600; color: #0f172a; margin-bottom: 0.25rem;">{{ $f['title'] }}</h3>
                                <p style="font-size: 0.8rem; color: #64748b; margin: 0; line-height: 1.5;">{{ $f['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- SNIPPET PREVIEW --}}
    <section style="padding: 4rem 0;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-2" style="color: #0f172a; font-size: 1.5rem; letter-spacing: -0.02em;">{{ __('Pronto in 30 secondi.') }}</h2>
                    <p style="color: #64748b; line-height: 1.7;">{{ __('Aggiungi il sito, copia lo snippet, incollalo nel tuo HTML. I dati iniziano ad arrivare subito.') }}</p>
                    <div class="d-flex flex-column gap-2 mt-3">
                        @php
                            $steps = [
                                ['num' => '1', 'text' => __('Crea il tuo sito nella dashboard')],
                                ['num' => '2', 'text' => __('Copia lo snippet generato')],
                                ['num' => '3', 'text' => __('Incollalo prima di </body>')],
                            ];
                        @endphp
                        @foreach ($steps as $s)
                            <div class="d-flex align-items-center gap-3">
                                <span style="width: 28px; height: 28px; border-radius: 50%; background: rgba(16,185,129,0.08); color: #10b981; display: flex; align-items: center; justify-content: center; font-family: 'JetBrains Mono', monospace; font-size: 0.7rem; font-weight: 700; flex-shrink: 0;">{{ $s['num'] }}</span>
                                <span style="font-size: 0.85rem; color: #334155;">{{ $s['text'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-6 offset-lg-1">
                    <div class="nd-float-delay" style="background: #0f172a; border-radius: 0.75rem; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.12);">
                        <div style="padding: 0.75rem 1rem; background: #1e293b; display: flex; align-items: center; gap: 0.375rem;">
                            <span style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444;"></span>
                            <span style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></span>
                            <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></span>
                            <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.65rem; color: #475569; margin-left: 0.5rem;">index.html</span>
                        </div>
                        <div style="padding: 1.25rem; font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; line-height: 1.8; color: #94a3b8; overflow-x: auto;">
                            <div><span style="color: #475569;">&lt;!--</span> <span style="color: #64748b;">{{ __('Incolla prima di &lt;/body&gt;') }}</span> <span style="color: #475569;">--&gt;</span></div>
                            <div><span style="color: #e879f9;">&lt;script</span> <span style="color: #38bdf8;">src</span>=<span style="color: #10b981;">"https://tuosito.com/js/stats.js"</span></div>
                            <div>&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #38bdf8;">data-site</span>=<span style="color: #10b981;">"pk_abc123..."</span></div>
                            <div>&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #38bdf8;">defer</span><span style="color: #e879f9;">&gt;&lt;/script&gt;</span></div>
                            <div style="margin-top: 0.75rem; color: #475569;">// {{ __('Opzionale: traccia eventi custom') }}</div>
                            <div><span style="color: #38bdf8;">window</span>.<span style="color: #f1f5f9;">downstage</span>.<span style="color: #fbbf24;">track</span>(<span style="color: #10b981;">'signup'</span>, {</div>
                            <div>&nbsp;&nbsp;<span style="color: #38bdf8;">plan</span>: <span style="color: #10b981;">'pro'</span></div>
                            <div>})</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section style="padding: 4rem 0 5rem;">
        <div class="container">
            <div class="text-center" style="background: linear-gradient(135deg, rgba(16,185,129,0.04) 0%, rgba(6,182,212,0.04) 100%); border: 1px solid #e2e8f0; border-radius: 1rem; padding: 3rem 2rem;">
                <h2 class="fw-bold mb-2" style="color: #0f172a; font-size: 1.5rem; letter-spacing: -0.02em;">{{ __('Pronto a tracciare?') }}</h2>
                <p class="mb-4" style="color: #64748b; max-width: 400px; margin-left: auto; margin-right: auto;">{{ __('Setup in meno di un minuto. Nessuna carta di credito richiesta.') }}</p>
                @guest
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('register') }}" class="btn btn-primary" style="padding: 0.6rem 1.75rem;">
                            {{ __('Crea account') }} <i class="fas fa-arrow-right ms-1" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-primary" style="padding: 0.6rem 1.75rem;">
                        {{ __('Vai alla dashboard') }} <i class="fas fa-arrow-right ms-1" style="font-size: 0.75rem;"></i>
                    </a>
                @endguest
            </div>
        </div>
    </section>
@endsection
