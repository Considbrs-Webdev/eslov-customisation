@php
    $icon = \EslovCustomisation\Modules\Navigation\IconNormalizer::forComponent(
        $item['icon'] ?? null,
        'arrow_forward',
    );
    if ($icon) {
        $icon['size'] = 'lg';
    }
@endphp
@if ($icon)
    @icon($icon)
    @endicon
@endif
@typography([
    'element' => 'span',
    'variant' => 'meta',
    'classList' => array_filter([
        'mod-navigation__bar-label',
        'u-text-decoration--underline',
        $labelClass ?? null,
    ]),
])
    {{ $item['title'] ?? '' }}
@endtypography
