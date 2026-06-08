@if (!empty($items))
    <div class="o-grid mod-navigation__grid mod-navigation__grid--default u-margin__top--4">
        @foreach ($items as $item)
            @php
                $icon = \EslovCustomisation\Modules\Navigation\IconNormalizer::forComponent($item['icon'] ?? null);
                if ($icon && !empty($item['color'])) {
                    $icon = \EslovCustomisation\Modules\Navigation\IconNormalizer::withContrastOn(
                        $icon,
                        (string) $item['color'],
                    );
                }
            @endphp
            <div class="o-grid-4@md mod-navigation__grid-item">
                <a
                    href="{{ $item['href'] ?? '#' }}"
                    class="mod-navigation__grid-link u-display--flex u-align-items--start u-gap__2 u-no-decoration u-height--100"
                >
                    @if ($icon)
                        @icon(array_merge($icon, ['size' => 'lg']))
                        @endicon
                    @endif
                    <span class="u-display--flex u-flex-direction--column u-gap__1">
                        @typography([
                            'variant' => 'h4',
                            'element' => 'span',
                            'classList' => ['u-text-decoration--underline', 'u-margin--0'],
                        ])
                            {{ $item['title'] ?? '' }}
                        @endtypography
                        @if (!empty($item['description']))
                            @typography([
                                'variant' => 'body',
                                'element' => 'p',
                                'classList' => ['u-margin--0'],
                            ])
                                {{ $item['description'] }}
                            @endtypography
                        @endif
                    </span>
                </a>
            </div>
        @endforeach
    </div>
@endif
