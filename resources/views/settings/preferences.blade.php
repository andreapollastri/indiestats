@extends('layouts.app')

@section('content')
    <div class="mb-4 mt-3">
        <p class="small fw-semibold mb-1 text-uppercase" style="letter-spacing: 0.06em; color: #94a3b8; font-size: 0.65rem;">{{ __('Navigazione') }}</p>
        <h1 class="h3 mb-0 fw-bold" style="color: #0f172a; letter-spacing: -0.02em;">{{ __('Impostazioni') }}</h1>
    </div>

    @include('partials.flash')

    <div class="card mb-4">
        <div class="card-header py-3">
            <h6 class="m-0" style="color: #10b981;">{{ __('Impostazioni generali') }}</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('preferences.update') }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="locale" class="form-label">{{ __('Lingua') }}</label>
                    <select id="locale" name="locale" class="form-select @error('locale') is-invalid @enderror" required>
                        @foreach ($locales as $code => $label)
                            <option value="{{ $code }}" @selected(old('locale', $locale) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('locale')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="timezone" class="form-label">{{ __('Fuso orario') }}</label>
                    <select id="timezone" name="timezone" class="form-select @error('timezone') is-invalid @enderror" required autocomplete="off" data-placeholder="{{ __('Cerca fuso orario...') }}">
                        @foreach ($timezones as $tz)
                            <option value="{{ $tz }}" @selected(old('timezone', $timezone) === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                    @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <p class="small mt-2 mb-0" style="color: #94a3b8;">{{ __('Le date e gli orari nell’app sono memorizzati in UTC e mostrati in questo fuso.') }}</p>
                </div>
                <button type="submit" class="btn btn-primary" data-test="save-preferences-button">{{ __('Salva') }}</button>
            </form>
        </div>
    </div>
@endsection
