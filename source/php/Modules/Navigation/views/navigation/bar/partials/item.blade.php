@php
    $icon = \EslovCustomisation\Modules\Navigation\IconNormalizer::forComponent(
        $item['icon'] ?? null,
        'arrow_forward',
    );
    if ($icon) {
        $icon['size'] = 'inherit';
        $icon['classList'] = array_merge($icon['classList'] ?? [], ['mod-navigation__bar-icon__glyph']);
    }
@endphp
@if ($icon)
    <span class="mod-navigation__bar-icon" aria-hidden="true">
        @icon($icon)
        @endicon
    </span>
@endif
<span class="mod-navigation__bar-label">{{ $item['title'] ?? '' }}</span>
