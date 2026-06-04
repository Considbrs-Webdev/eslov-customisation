@if (!empty($items))
    <nav class="mod-navigation__bar mod-navigation__bar--outline">
        <div class="o-container">
            <ul class="mod-navigation__bar-list mod-navigation__bar-list--outline u-unlist u-margin--0">
                @foreach ($items as $item)
                    <li class="mod-navigation__bar-item mod-navigation__bar-item--contents">
                        <a
                            href="{{ $item['href'] ?? '#' }}"
                            class="mod-navigation__bar-link mod-navigation__bar-link--outline u-no-decoration u-border--1 u-border__color--primary u-rounded u-color__text--primary"
                        >
                            @include('navigation.bar.partials.item', ['item' => $item])
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
@endif
