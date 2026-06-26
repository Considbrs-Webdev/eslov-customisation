@if (!empty($image['src']))
    <img
        src="{{ $image['src'] }}"
        alt="{{ $image['alt'] ?? '' }}"
        class="mod-navigation__tree-image-el"
        loading="lazy"
    />
@endif
