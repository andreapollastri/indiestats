@props([
    'id',
    'title',
    'description' => null,
    'expanded' => false,
])

@php
    $sectionId = 'pa-stats-section-' . $id;
@endphp

<div {{ $attributes->merge(['class' => 'accordion pa-stats-section mb-3']) }} id="{{ $sectionId }}">
    <div class="accordion-item border-0">
        <h2 class="accordion-header">
            <button
                class="accordion-button pa-stats-section__toggle {{ $expanded ? '' : 'collapsed' }} py-2 px-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#{{ $sectionId }}-body"
                aria-expanded="{{ $expanded ? 'true' : 'false' }}"
                aria-controls="{{ $sectionId }}-body"
            >
                <span class="d-flex flex-column flex-sm-row align-items-sm-center gap-1 gap-sm-2 text-start w-100 pe-2">
                    <span class="pa-stats-section__title">{{ $title }}</span>
                    @if ($description)
                        <span class="pa-stats-section__desc">{{ $description }}</span>
                    @endif
                </span>
            </button>
        </h2>
        <div
            id="{{ $sectionId }}-body"
            class="accordion-collapse collapse {{ $expanded ? 'show' : '' }}"
            data-bs-parent="#{{ $sectionId }}"
        >
            <div class="accordion-body px-0 pt-2 pb-0">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
