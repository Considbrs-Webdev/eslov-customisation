<ul class="{{ $class }}">
    @foreach ($events as $event)
        <li class="{{ $baseClass }}__event @if (!empty($event['active_step'])){{ $baseClass }}__event--active @endif @if (!empty($event['passed_step'])) {{ $baseClass }}__event--passed @endif">
            @if(!$sequential)
                <div class="{{ $baseClass }}__date u-visibility--hidden@sm u-visibility--hidden@xs">
                    {!! $event['timelineDate'] !!}
                </div>
            @endif
            <div class="{{ $baseClass }}__marker">
                @if(!$sequential)
                    <div class="{{ $baseClass }}__date u-visibility--hidden@md u-visibility--hidden@lg u-visibility--hidden@xl">
                        {!! $event['timelineDate'] !!}
                    </div>
                @endif
            </div>

            @card([
                'classList' => [$baseClass . '__event__card'],
                'context' => 'module.timeline.card',
                'link' => $event['link'],
                'heading' => $event['title'],
                'content' => $event['content'],
                'image' => isset($event['imageSrc']) && is_array($event['imageSrc'])
                    ? [
                        'src' => $event['imageSrc'][0] ?? null,
                        'alt' => $event['title'],
                        'backgroundColor' => 'none'
                    ]
                    : ($event['imageSrc'] ?? null),
            ])
            @endcard
        </li>
    @endforeach
</ul>
