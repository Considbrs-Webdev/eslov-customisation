<ul class="{{ $class }}">
    @foreach ($events as $event)
        <li class="{{ $baseClass }}__event @if (!empty($event['active_step'])){{ $baseClass }}__event--active @endif @if (!empty($event['passed_step'])) {{ $baseClass }}__event--passed @endif">
            <div class="{{ $baseClass }}__marker">
                @if (!empty($event['active_step']))
                    @icon([
                        'icon' => 'play_arrow',
                        'filled' => true,
                        'decorative' => true,
                    ])
                    @endicon
                @elseif (!empty($event['passed_step']))
                    @icon([
                        'icon' => 'check',
                        'filled' => false,
                        'decorative' => true,
                    ])
                    @endicon
                @endif
            </div>

            @card([
                'classList' => [$baseClass . '__event__card'],
                'context' => 'module.timeline.card',
                'link' => $event['link'],
                'meta' => $sequential ? '' : ($event['timelineDate'] ?? ''),
                'metaFirst' => !$sequential,
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
