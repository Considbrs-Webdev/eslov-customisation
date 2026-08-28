@paper([
    'classList' => [
        'u-padding__x--3',
        'u-padding__y--3',
        'u-padding__x--4@md',
        'u-padding__y--4@md',
        'u-padding__x--4@lg',
        'u-padding__y--4@lg',
        'u-padding__x--4@xl',
        'u-padding__y--4@xl'
    ]])
    @typography([ 'element' => 'h2', 'variant' => 'h2', 'classList' => ['u-margin__top--0'], 'attributeList' => ['style' => 'color: var(--color-secondary-contrasting);'] ])
        {!! $lang->relatedEventsTitle !!}
    @endtypography
    @include('posts-list', $postsListData)
@endpaper
