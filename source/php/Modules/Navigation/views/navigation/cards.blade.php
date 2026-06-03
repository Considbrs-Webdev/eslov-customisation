@if (!empty($items))
    <div class="o-grid mod-navigation__cards-grid u-margin__top--2">
        @foreach ($items as $item)
            @php
                $description = $item['description'] ?? '';
                if ($description === '' && !empty($item['post']) && $item['post'] instanceof \WP_Post) {
                    $description = wp_trim_words($item['post']->post_content ?? '', 8, '...');
                }
                $hasImage = !empty($item['image']);
                $cardIcon = $hasImage
                    ? null
                    : \EslovCustomisation\Modules\Navigation\IconNormalizer::forComponent($item['icon'] ?? null);
            @endphp
            <div class="o-grid-4@md mod-navigation__card-item">
                @card([
                    'link' => $item['href'] ?? '',
                    'imageFirst' => $hasImage,
                    'heading' => $item['title'] ?? '',
                    'content' => $description,
                    'classList' => ['u-height--100', 'c-card--flat'],
                    'containerAware' => true,
                    'hasAction' => true,
                    'hasPlaceholder' => false,
                    'image' => $hasImage ? ($item['image'] ?? '') : '',
                    'icon' => $cardIcon,
                    'iconBackgroundColor' => !empty($item['color']) ? $item['color'] : null,
                ])
                @endcard
            </div>
        @endforeach
    </div>
@endif
