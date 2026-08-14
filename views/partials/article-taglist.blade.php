@if (!empty($tags))
    @tags([
        'tags' => $tags,
        'tagsStyle' => 'pill',
        'beforeLabel' => '',
        'classList' => ['u-margin__bottom--2', 'eslov-article-taglist'],
    ])
    @endtags
@endif
