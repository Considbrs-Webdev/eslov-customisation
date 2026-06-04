@if (!empty($children))
    <ul class="mod-navigation__tree-children">
        @foreach ($children as $child)
            <li>
                @link([
                    'href' => $child['href'] ?? '',
                    'classList' => ['mod-navigation__tree-child-link'],
                ])
                    {{ $child['title'] ?? '' }}
                @endlink
            </li>
        @endforeach
    </ul>
@endif
