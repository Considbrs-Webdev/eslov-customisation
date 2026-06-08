@if (!empty($items))
    <div class="o-grid mod-navigation__grid mod-navigation__grid--blocks u-margin__top--4">
        @foreach ($items as $item)
            <div class="o-grid-4@md mod-navigation__grid-block">
                @card([
                    'link' => $item['href'] ?? '',
                    'heading' => $item['title'] ?? '',
                    'content' => $item['description'] ?? '',
                    'classList' => ['u-height--100', 'c-card--flat'],
                    'containerAware' => true,
                    'hasAction' => true,
                ])
                @endcard
            </div>
        @endforeach
    </div>
@endif
