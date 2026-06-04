@if (!empty($items))
    @group([
        'display' => 'flex',
        'flexWrap' => 'wrap',
        'gap' => 2,
        'classList' => ['mod-navigation__buttons']
    ])
        @foreach ($items as $item)
            @php
                $variant = $item['buttonVariant'] ?? 'default';
                $color = match ($variant) {
                    'primary' => 'primary',
                    'secondary' => 'secondary',
                    default => 'default',
                };
                $style = $variant === 'default' ? 'basic' : 'filled';
                $icon = \EslovCustomisation\Modules\Navigation\IconNormalizer::forButton($item['icon'] ?? null);
            @endphp
            @button([
                'text' => $item['title'] ?? '',
                'href' => $item['href'] ?? '',
                'color' => $color,
                'style' => $style,
                'icon' => $icon,
                'classList' => ['u-margin__bottom--05'],
            ])
            @endbutton
        @endforeach
    @endgroup
@endif
