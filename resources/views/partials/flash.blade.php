@if (!empty($flashSuccess) || !empty($flashError) || $errors->any())
    <div class="mb-4">
        @if (!empty($flashSuccess))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ $flashSuccess }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Chiudi') }}"></button>
            </div>
        @endif
        @if (!empty($flashError))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $flashError }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Chiudi') }}"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Chiudi') }}"></button>
            </div>
        @endif
    </div>
@endif
