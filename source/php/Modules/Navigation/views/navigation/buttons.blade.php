@if (!empty($items))
    @group([
        'display' => 'flex',
        'flexWrap' => 'wrap',
        'gap' => 2,
        'classList' => ['mod-navigation__buttons', 'c-group--skip-child-normalization'],
    ])
        @foreach ($items as $item)
            @php
                $color = match ($item['buttonVariant'] ?? 'default') {
                    'primary', 'secondary' => $item['buttonVariant'] ?? 'default',
                    default => 'default',
                };
                $icon = \EslovCustomisation\Modules\Navigation\IconNormalizer::forButton($item['icon'] ?? null);
            @endphp
            @button([
                'text' => $item['title'] ?? '',
                'href' => $item['href'] ?? '',
                'color' => $color,
                'style' => 'filled',
                'icon' => $icon,
                'classList' => ['u-margin__bottom--05'],
            ])
            @endbutton
        @endforeach
    @endgroup
@endif
