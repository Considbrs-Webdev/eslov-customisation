@if (!empty($items))
    <ul class="mod-navigation__tree mod-navigation__tree--highlighted unlist">
        @foreach ($items as $item)
            <li class="mod-navigation__tree-item">
                <div class="mod-navigation__tree-highlighted-item">
                    <div class="mod-navigation__tree-highlighted-block u-color__bg--secondary">
                        @if (!empty($item['image']))
                            @include('navigation.tree.partials.image', ['image' => $item['image']])
                        @endif
                        <div class="mod-navigation__tree-highlighted-body">
                            @include('navigation.tree.partials.item-heading', ['item' => $item])
                        </div>
                    </div>
                    @include('navigation.tree.partials.child-links', ['children' => $item['children'] ?? []])
                </div>
            </li>
        @endforeach
    </ul>
@endif
