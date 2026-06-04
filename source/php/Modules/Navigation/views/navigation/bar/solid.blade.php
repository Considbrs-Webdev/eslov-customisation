@if (!empty($items))
    <nav class="mod-navigation__bar mod-navigation__bar--solid u-color__bg--primary">
        <div class="o-container">
            <ul class="mod-navigation__bar-list u-unlist u-margin--0">
                @foreach ($items as $item)
                    <li class="mod-navigation__bar-item mod-navigation__bar-item--contents">
                        <a
                            href="{{ $item['href'] ?? '#' }}"
                            class="mod-navigation__bar-link u-no-decoration u-color__text--primary-contrast"
                        >
                            @include('navigation.bar.partials.item', ['item' => $item])
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
@endif
