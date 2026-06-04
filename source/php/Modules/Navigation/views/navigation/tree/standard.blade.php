@if (!empty($items))
    <ul class="mod-navigation__tree mod-navigation__tree--standard u-unlist">
        @foreach ($items as $item)
            <li class="mod-navigation__tree-item u-padding__y--2 u-border__bottom">
                <div class="o-grid mod-navigation__tree-row">
                    @if (!empty($item['image']))
                        <div class="o-grid-3@md mod-navigation__tree-image">
                            @if (is_object($item['image']) && method_exists($item['image'], 'getUrl'))
                                <img src="{{ $item['image']->getUrl() }}" alt="" class="u-width--100" />
                            @elseif (!empty($item['image']['src']))
                                <img src="{{ $item['image']['src'] }}" alt="{{ $item['image']['alt'] ?? '' }}" class="u-width--100" />
                            @endif
                        </div>
                    @endif
                    <div class="o-grid-9@md">
                        @typography([
                            'element' => 'h3',
                            'variant' => 'h3',
                            'classList' => ['mod-navigation__tree-title'],
                        ])
                            <a href="{{ $item['href'] ?? '#' }}">{{ $item['title'] ?? '' }}</a>
                        @endtypography
                        @if (!empty($item['description']))
                            <p class="mod-navigation__tree-description">{{ $item['description'] }}</p>
                        @endif
                        @if (!empty($item['children']))
                            @group([
                                'display' => 'flex',
                                'wrap' => 'wrap',
                                'gap' => 2,
                                'classList' => ['mod-navigation__tree-children', 'mod-navigation__tree-children--standard', 'u-margin__top--1'],
                            ])
                                @foreach ($item['children'] as $child)
                                    @button([
                                        'text' => $child['title'] ?? '',
                                        'href' => $child['href'] ?? '',
                                        'color' => 'secondary',
                                        'style' => 'filled',
                                        'size' => 'sm',
                                    ])
                                    @endbutton
                                @endforeach
                            @endgroup
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
