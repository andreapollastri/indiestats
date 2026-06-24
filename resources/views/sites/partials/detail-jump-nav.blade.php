@php
    $sections = [
        ['id' => 'content', 'label' => __('Contenuto')],
        ['id' => 'traffic', 'label' => __('Traffico')],
        ['id' => 'utm', 'label' => __('UTM')],
        ['id' => 'tech', 'label' => __('Tecnologia')],
        ['id' => 'geo', 'label' => __('Geografia')],
    ];
@endphp

<nav class="pa-detail-jump-nav mb-3" aria-label="{{ __('Vai alla sezione') }}">
    @foreach ($sections as $section)
        <button
            type="button"
            class="pa-detail-jump-nav__item"
            data-pa-jump-section="{{ $section['id'] }}"
        >{{ $section['label'] }}</button>
    @endforeach
</nav>
