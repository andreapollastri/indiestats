@extends('layouts.app')

@section('content')
    <div class="row g-3 align-items-start align-items-lg-center mb-4">
        <div class="col-12 col-lg order-2 order-lg-1">
            <h1 class="h3 mb-1 fw-bold pa-page-header__title">{{ __('I tuoi siti') }}</h1>
            <p class="small mb-0 pa-text-muted-soft">
                @if ($canManageSites)
                    {{ __('Aggiungi un sito e incolla lo snippet sulle pagine che vuoi misurare.') }}
                @else
                    {{ __('Siti a cui hai accesso. Contatta un amministratore per nuove assegnazioni.') }}
                @endif
            </p>
        </div>
        @if ($canManageSites)
            <div class="col-12 col-lg-auto order-1 order-lg-2">
                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createSiteModal"
                >
                    <i class="fas fa-plus me-1" aria-hidden="true"></i>{{ __('Nuovo sito') }}
                </button>
            </div>
        @endif
    </div>

    @include('partials.flash')

    @if (! empty($siteCreated))
        @include('sites.partials.created-site-snippet', ['siteCreated' => $siteCreated])
    @endif

    @if (empty($sites) || count($sites) === 0)
        <p class="small pa-text-muted-soft">
            @if ($canManageSites)
                {{ __('Nessun sito ancora. Creane uno con Nuovo sito.') }}
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
                'openCreateSiteModal' => $openCreateSiteModal,
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
    @endif

    @if ($canManageSites)
        <div
            class="modal fade"
            id="createSiteModal"
            tabindex="-1"
            aria-labelledby="createSiteModalLabel"
            aria-hidden="true"
            @if ($openCreateSiteModal) data-pa-open-on-load="1" @endif
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="createSiteForm" method="POST" action="{{ route('sites.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="createSiteModalLabel">{{ __('Nuovo sito') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Chiudi') }}"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small mb-3 pa-text-muted-soft">{{ __('Nome interno e gli host da cui è consentito inviare dati (stesso dominio del sito dove incolli lo snippet).') }}</p>
                            <div class="mb-3">
                                <label for="create_site_name" class="form-label">{{ __('Nome') }}</label>
                                <input id="create_site_name" name="name" type="text" class="form-control {{ $openCreateSiteModal && $errors->has('name') ? 'is-invalid' : '' }}" value="{{ $openCreateSiteModal ? old('name') : '' }}" required autocomplete="off" placeholder="{{ __('Il mio blog') }}">
                                @if ($openCreateSiteModal)
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @endif
                            </div>
                            <div class="mb-0">
                                <label for="create_site_allowed_domains" class="form-label">{{ __('Domini consentiti') }} <span class="text-danger">*</span></label>
                                <input id="create_site_allowed_domains" name="allowed_domains" type="text" class="form-control {{ $openCreateSiteModal && $errors->has('allowed_domains') ? 'is-invalid' : '' }}" value="{{ $openCreateSiteModal ? old('allowed_domains') : '' }}" required autocomplete="off" placeholder="{{ __('esempio.com, www.esempio.com') }}">
                                @if ($openCreateSiteModal)
                                    @error('allowed_domains')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Annulla') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Aggiungi sito') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if (! empty($sites) && count($sites) > 0)
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
                            @php $editingSite = (bool) session('edit_site_public_key'); @endphp
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="edit_site_name" class="form-label">{{ __('Nome') }}</label>
                                    <input id="edit_site_name" name="name" type="text" class="form-control {{ $editingSite && $errors->has('name') ? 'is-invalid' : '' }}" value="{{ $editingSite ? old('name') : '' }}" required autocomplete="off">
                                    @if ($editingSite)
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <div class="mb-0">
                                    <label for="edit_site_allowed_domains" class="form-label">{{ __('Domini consentiti') }} <span class="text-danger">*</span></label>
                                    <input id="edit_site_allowed_domains" name="allowed_domains" type="text" class="form-control {{ $editingSite && $errors->has('allowed_domains') ? 'is-invalid' : '' }}" value="{{ $editingSite ? old('allowed_domains') : '' }}" required autocomplete="off" placeholder="{{ __('esempio.com, www.esempio.com') }}">
                                    @if ($editingSite)
                                        @error('allowed_domains')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @endif
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
