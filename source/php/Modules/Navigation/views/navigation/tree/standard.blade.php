@if (!empty($items))
    <ul class="mod-navigation__tree mod-navigation__tree--standard unlist">
        @foreach ($items as $item)
            <li class="mod-navigation__tree-item u-padding__y--2 u-border__bottom">
                <div class="mod-navigation__tree-row">
                    @if (!empty($item['image']))
                        <div class="mod-navigation__tree-image">
                            @include('navigation.tree.partials.image', ['image' => $item['image']])
                        </div>
                    @endif
                    <div class="mod-navigation__tree-content">
                        @include('navigation.tree.partials.item-heading', ['item' => $item])
                        @include('navigation.tree.partials.child-links', [
                            'children' => $item['children'] ?? [],
                            'classList' => ['mod-navigation__tree-children--standard'],
                        ])
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
