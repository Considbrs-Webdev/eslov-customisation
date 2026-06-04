@if (!empty($items))
    <ul class="mod-navigation__tree mod-navigation__tree--highlighted u-unlist">
        @foreach ($items as $item)
            <li class="mod-navigation__tree-item u-margin__bottom--3">
                @card([
                    'link' => $item['href'] ?? '',
                    'heading' => $item['title'] ?? '',
                    'content' => $item['description'] ?? '',
                    'image' => $item['image'] ?? '',
                    'classList' => ['mod-navigation__tree-card'],
                    'containerAware' => true,
                    'hasAction' => true,
                    'hasPlaceholder' => empty($item['image']),
                ])
                @endcard
                @include('navigation.tree.partials.child-links', ['children' => $item['children'] ?? []])
            </li>
        @endforeach
    </ul>
@endif
