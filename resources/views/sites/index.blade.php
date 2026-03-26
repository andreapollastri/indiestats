@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1 fw-bold" style="color: #0f172a; letter-spacing: -0.02em;">{{ __('I tuoi siti') }}</h1>
        <p class="small mb-0" style="color: #94a3b8;">{{ __('Aggiungi un sito e incolla lo snippet sulle pagine che vuoi misurare.') }}</p>
    </div>

    @include('partials.flash')

    <div class="card mb-4">
        <div class="card-header py-3">
            <h6 class="m-0" style="color: #10b981;">{{ __('Nuovo sito') }}</h6>
        </div>
        <div class="card-body">
            <p class="small mb-3" style="color: #94a3b8;">{{ __('Nome interno e gli host da cui è consentito inviare dati (stesso dominio del sito dove incolli lo snippet).') }}</p>
            <form method="POST" action="{{ route('sites.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">{{ __('Nome') }}</label>
                        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required autocomplete="off" placeholder="{{ __('Il mio blog') }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="allowed_domains" class="form-label">{{ __('Domini consentiti') }} <span style="color: #ef4444;">*</span></label>
                        <input id="allowed_domains" name="allowed_domains" type="text" class="form-control @error('allowed_domains') is-invalid @enderror" value="{{ old('allowed_domains') }}" required autocomplete="off" placeholder="esempio.com, www.esempio.com">
                        @error('allowed_domains')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary">{{ __('Aggiungi sito') }}</button>
                </div>
            </form>
        </div>
    </div>

    @if (empty($sites) || count($sites) === 0)
        <p class="small" style="color: #94a3b8;">{{ __('Nessun sito ancora. Creane uno qui sopra.') }}</p>
    @else
        @foreach ($sites as $site)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-2 align-items-start align-items-md-center mb-2">
                        <div class="col-12 col-md order-2 order-md-1">
                            <h2 class="h6 mb-1 fw-bold">
                                <a href="{{ route('sites.show', $site['public_key']) }}" class="text-decoration-none" style="color: #0f172a;">{{ $site['name'] }}</a>
                            </h2>
                            <p class="small font-monospace mb-0" style="color: #94a3b8; font-size: 0.7rem;">{{ $site['public_key'] }}</p>
                        </div>
                        <div class="col-12 col-md-auto d-flex flex-wrap align-items-center justify-content-end gap-1 ms-md-auto order-1 order-md-2">
                            <a href="{{ route('sites.show', $site['public_key']) }}" class="btn btn-outline-primary btn-sm">{{ __('Statistiche') }}</a>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-copy="{{ $site['embed_code'] }}" data-copy-done="{{ __('Copiato') }}" title="{{ __('Copia snippet') }}"><i class="fas fa-copy"></i></button>
                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteSiteModal" data-delete-url="{{ route('sites.destroy', $site['public_key']) }}" data-site-name="{{ e($site['name']) }}" title="{{ __('Elimina') }}" aria-label="{{ __('Elimina') }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <pre class="mb-0" style="max-height: 8rem; overflow: auto; white-space: pre-wrap;">{{ $site['embed_code'] }}</pre>
                </div>
            </div>
        @endforeach

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
@endsection
