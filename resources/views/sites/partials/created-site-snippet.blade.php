@php
    /** @var array{name: string, embed_code: string, stats_url: string} $siteCreated */
@endphp
<div class="card mb-4 pa-stats-table-card border-success" id="pa-site-created-panel">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="m-0 pa-text-accent">{{ __('Sito creato: :name', ['name' => $siteCreated['name']]) }}</h6>
            <small>{{ __('Incolla lo snippet sul sito per iniziare a raccogliere statistiche.') }}</small>
        </div>
        <a href="{{ $siteCreated['stats_url'] }}" class="btn btn-sm btn-outline-primary">{{ __('Apri statistiche') }}</a>
    </div>
    <div class="card-body">
        <ol class="small pa-text-muted-soft mb-3 ps-3">
            <li class="mb-1">{{ __('Copia il codice qui sotto.') }}</li>
            <li class="mb-1">{{ __('Incollalo prima del tag') }} <code>&lt;/body&gt;</code> {{ __('su ogni pagina del sito, oppure nel layout condiviso (header/footer del tema).') }}</li>
            <li class="mb-1">{{ __('Pubblica le modifiche e visita una pagina del sito per generare la prima visita.') }}</li>
            <li>{{ __('Torna qui e apri le statistiche per verificare che i dati arrivino (può servire un minuto).') }}</li>
        </ol>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <span class="small fw-semibold pa-text-muted-soft">{{ __('Codice di inclusione') }}</span>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                data-copy="{{ $siteCreated['embed_code'] }}"
                data-copy-done="{{ __('Copiato') }}"
            >
                <i class="fas fa-copy me-1" aria-hidden="true"></i>{{ __('Copia snippet') }}
            </button>
        </div>
        <pre class="mb-0 user-select-all" style="max-height: 10rem; overflow: auto; white-space: pre-wrap;">{{ $siteCreated['embed_code'] }}</pre>

        <p class="small pa-text-muted-soft mt-3 mb-0">
            {{ __('Lo script registra automaticamente visualizzazioni, tempo in pagina, click in uscita e parametri UTM. Per eventi personalizzati usa') }}
            <code>window.indiestats.track('nome_evento')</code>.
        </p>
    </div>
</div>
