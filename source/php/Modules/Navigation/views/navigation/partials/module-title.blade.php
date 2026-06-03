@if (!$hideTitle && !empty($postTitle))
    @typography([
        'id' => 'mod-navigation-' . $ID . '-label',
        'element' => 'h2',
        'autopromote' => true,
        'classList' => ['module-title'],
    ])
        {!! $postTitle !!}
    @endtypography
@endif
