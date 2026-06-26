@typography([
    'element' => 'h3',
    'variant' => 'h3',
    'classList' => ['mod-navigation__tree-title', 'u-margin--0'],
])
    <a href="{{ $item['href'] ?? '#' }}" class="mod-navigation__tree-title-link">
        {{ $item['title'] ?? '' }}
    </a>
@endtypography
@if (!empty($item['description']))
    @typography([
        'element' => 'p',
        'variant' => 'body',
        'classList' => ['u-margin__top--1', 'u-margin__bottom--0'],
    ])
        {{ $item['description'] }}
    @endtypography
@endif
