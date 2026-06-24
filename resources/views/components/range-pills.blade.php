@props([
    'ranges',
    'current',
    'urls',
])

<div {{ $attributes->merge(['class' => 'pa-range-pills']) }} role="group" aria-label="{{ __('Periodo') }}">
    @foreach ($ranges as $key => $label)
        <a
            href="{{ $urls[$key] ?? '#' }}"
            class="pa-range-pills__item {{ $current === $key ? 'pa-range-pills__item--active' : '' }}"
            @if ($current === $key) aria-current="page" @endif
        >{{ $label }}</a>
    @endforeach
</div>
