@if (!empty($items))
    <div class="mod-navigation__inline u-color__bg--primary u-color__text--primary-contrast u-padding--1">
        @group([
            'display' => 'flex',
            'flexWrap' => 'wrap',
            'gap' => 1,
            'justifyContent' => 'center',
            'classList' => ['mod-navigation__inline-row', 'c-group--skip-child-normalization'],
        ])
            @foreach ($items as $item)
                @php
                    $icon = \EslovCustomisation\Modules\Navigation\IconNormalizer::forButton($item['icon'] ?? null);
                @endphp
                @button([
                    'text' => $item['title'] ?? '',
                    'href' => $item['href'] ?? '',
                    'color' => 'primary',
                    'style' => 'basic',
                    'icon' => $icon,
                    'classList' => ['mod-navigation__inline-button'],
                ])
                @endbutton
            @endforeach
        @endgroup
    </div>
@endif
