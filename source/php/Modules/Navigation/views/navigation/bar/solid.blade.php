@if (!empty($items))
    <nav class="mod-navigation__bar mod-navigation__bar--solid u-color__bg--primary">
        <div class="o-container">
            <ul
                class="mod-navigation__bar-list u-unlist u-display--flex u-flex-wrap--wrap u-justify-content--space-evenly u-align-items--stretch u-margin--0"
            >
                @foreach ($items as $item)
                    <li class="mod-navigation__bar-item u-display--flex">
                        <a
                            href="{{ $item['href'] ?? '#' }}"
                            class="mod-navigation__bar-link u-display--flex u-flex-direction--column u-align-items--center u-justify-content--center u-text-align--center u-gap__2 u-padding__y--2 u-padding__x--1 u-no-decoration u-color__text--primary-contrast u-width--100"
                        >
                            @include('navigation.bar.partials.item', ['item' => $item])
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
@endif
