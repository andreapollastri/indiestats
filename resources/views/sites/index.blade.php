@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1 fw-bold pa-page-header__title">{{ __('I tuoi siti') }}</h1>
        <p class="small mb-0 pa-text-muted-soft">
            @if ($canManageSites)
                {{ __('Aggiungi un sito e incolla lo snippet sulle pagine che vuoi misurare.') }}
            @else
                {{ __('Siti a cui hai accesso. Contatta un amministratore per nuove assegnazioni.') }}
            @endif
        </p>
    </div>

    @include('partials.flash')

    @if (! empty($siteCreated))
        @include('sites.partials.created-site-snippet', ['siteCreated' => $siteCreated])
    @endif

    @if ($canManageSites)
        <div class="card mb-4 pa-stats-table-card">
            <div class="card-header py-3">
                <h6 class="m-0">{{ __('Nuovo sito') }}</h6>
            </div>
            <div class="card-body">
                <p class="small mb-3 pa-text-muted-soft">{{ __('Nome interno e gli host da cui è consentito inviare dati (stesso dominio del sito dove incolli lo snippet).') }}</p>
                <form method="POST" action="{{ route('sites.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">{{ __('Nome') }}</label>
                            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required autocomplete="off" placeholder="{{ __('Il mio blog') }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="allowed_domains" class="form-label">{{ __('Domini consentiti') }} <span class="text-danger">*</span></label>
                            <input id="allowed_domains" name="allowed_domains" type="text" class="form-control @error('allowed_domains') is-invalid @enderror" value="{{ old('allowed_domains') }}" required autocomplete="off" placeholder="{{ __('esempio.com, www.esempio.com') }}">
                            @error('allowed_domains')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">{{ __('Aggiungi sito') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if (empty($sites) || count($sites) === 0)
        <p class="small pa-text-muted-soft">
            @if ($canManageSites)
                {{ __('Nessun sito ancora. Creane uno qui sopra.') }}
            @else
                {{ __('Nessun sito assegnato al tuo account.') }}
            @endif
        </p>
    @else
        <div class="card mb-4 pa-stats-table-card">
            <div class="card-header py-3">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                    <div>
                        <h6 class="m-0">{{ __('Siti') }}</h6>
                        <small>{{ __('Cerca, ordina e filtra l\'elenco dei siti') }}</small>
                    </div>
                    <div class="pa-sites-filter" style="min-width: min(100%, 16rem);">
                        <label for="pa-sites-index-filter" class="visually-hidden">{{ __('Cerca sito…') }}</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" aria-hidden="true"><i class="fas fa-search"></i></span>
                            <input
                                type="search"
                                id="pa-sites-index-filter"
                                class="form-control"
                                placeholder="{{ __('Cerca sito…') }}"
                                autocomplete="off"
                            >
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table
                        id="pa-sites-index-table"
                        class="table table-bordered table-sm mb-0 w-100 pa-site-dt"
                        width="100%"
                    >
                        <thead>
                            <tr>
                                <th>{{ __('Nome') }}</th>
                                <th>{{ __('Chiave') }}</th>
                                <th>{{ __('Domini consentiti') }}</th>
                                <th>{{ __('Creato') }}</th>
                                <th class="text-end">{{ __('Azioni') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        @php
            $sitesIndexConfig = [
                'canManageSites' => $canManageSites,
                'editSitePublicKey' => session('edit_site_public_key'),
                'sites' => collect($sites)->map(fn (array $site) => [
                    'name' => $site['name'],
                    'public_key' => $site['public_key'],
                    'allowed_domains' => $site['allowed_domains'],
                    'created_at' => $site['created_at'],
                    'created_at_label' => \Illuminate\Support\Carbon::parse($site['created_at'])->translatedFormat('j M Y'),
                    'embed_code' => $site['embed_code'],
                    'show_url' => route('sites.show', $site['public_key']),
                    'update_url' => $canManageSites ? route('sites.update', $site['public_key']) : null,
                    'destroy_url' => $canManageSites ? route('sites.destroy', $site['public_key']) : null,
                ])->values()->all(),
                'labels' => [
                    'stats' => __('Statistiche'),
                    'copy' => __('Copia snippet'),
                    'copyDone' => __('Copiato'),
                    'edit' => __('Modifica'),
                    'delete' => __('Elimina'),
                ],
            ];
        @endphp
        <script type="application/json" id="pa-sites-index-config">
@json($sitesIndexConfig)
        </script>

        @if ($canManageSites)
            <div class="modal fade" id="editSiteModal" tabindex="-1" aria-labelledby="editSiteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="editSiteForm" method="POST" action="">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title" id="editSiteModalLabel">{{ __('Modifica sito') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Chiudi') }}"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="edit_site_name" class="form-label">{{ __('Nome') }}</label>
                                    <input id="edit_site_name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required autocomplete="off">
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-0">
                                    <label for="edit_site_allowed_domains" class="form-label">{{ __('Domini consentiti') }} <span class="text-danger">*</span></label>
                                    <input id="edit_site_allowed_domains" name="allowed_domains" type="text" class="form-control @error('allowed_domains') is-invalid @enderror" value="{{ old('allowed_domains') }}" required autocomplete="off" placeholder="{{ __('esempio.com, www.esempio.com') }}">
                                    @error('allowed_domains')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Annulla') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('Salva modifiche') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="deleteSiteModal" tabindex="-1" aria-labelledby="deleteSiteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="deleteSiteForm" method="POST" action="">
                            @csrf
                            @method('DELETE')
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteSiteModalLabel">{{ __('Conferma eliminazione') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Chiudi') }}"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small">{{ __('Eliminazione definitiva del sito') }} <span id="deleteSiteModalName" class="fw-bold"></span>. {{ __('Tutte le statistiche e gli obiettivi collegati verranno rimossi. Questa azione è irreversibile.') }}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Annulla') }}</button>
                                <button type="submit" class="btn btn-danger">{{ __('Elimina definitivamente') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endif
@endsection
