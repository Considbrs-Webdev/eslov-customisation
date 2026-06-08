@group([
    'display' => 'flex',
    'flexWrap' => 'wrap',
    'gap' => 2,
    'classList' => ['u-margin__bottom--2', 'eslov-child-page-buttons', 'c-group--skip-child-normalization'],
])
    @foreach ($items as $item)
        @button([
            'text' => $item['label'],
            'href' => $item['href'],
            'color' => 'secondary',
            'style' => 'filled',
            'classList' => ['u-margin__bottom--05'],
        ])
        @endbutton
    @endforeach
@endgroup
