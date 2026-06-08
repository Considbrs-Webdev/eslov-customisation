@if (!$hideTitle && !empty($postTitle))
    @typography([
        'id' => 'mod-navigation-' . $ID . '-label',
        'element' => 'h2',
        'autopromote' => true,
        'classList' => ['module-title', 'u-margin__bottom--3'],
    ])
        {!! $postTitle !!}
    @endtypography
@endif
