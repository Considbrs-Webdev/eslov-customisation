@if (!empty($items))
    <ul class="mod-navigation__tree mod-navigation__tree--highlighted u-unlist">
        @foreach ($items as $item)
            <li class="mod-navigation__tree-item u-margin__bottom--3">
                @card([
                    'link' => $item['href'] ?? '',
                    'heading' => $item['title'] ?? '',
                    'content' => $item['description'] ?? '',
                    'image' => $item['image'] ?? '',
                    'classList' => ['mod-navigation__tree-card'],
                    'containerAware' => true,
                    'hasAction' => true,
                    'hasPlaceholder' => empty($item['image']),
                ])
                @endcard
                @if (!empty($item['children']))
                    @group([
                        'display' => 'flex',
                        'flexWrap' => 'wrap',
                        'gap' => 2,
                        'classList' => ['mod-navigation__tree-children', 'u-margin__top--1'],
                    ])
                        @foreach ($item['children'] as $child)
                            @button([
                                'text' => $child['title'] ?? '',
                                'href' => $child['href'] ?? '',
                                'color' => 'secondary',
                                'style' => 'basic',
                            ])
                            @endbutton
                        @endforeach
                    @endgroup
                @endif
            </li>
        @endforeach
    </ul>
@endif
