@if (!empty($image['src']))
    <img
        src="{{ $image['src'] }}"
        @if (!empty($image['srcset']))
            srcset="{{ $image['srcset'] }}"
            sizes="12.5rem"
        @endif
        alt="{{ $image['alt'] ?? '' }}"
        class="mod-navigation__tree-image-el"
        loading="lazy"
    />
@endif
