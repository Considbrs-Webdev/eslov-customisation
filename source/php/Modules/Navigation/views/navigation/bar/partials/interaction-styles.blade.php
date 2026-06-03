{{-- Token-based hover/focus/visited; v41 has no primary-dark hover utility (LTS: hover:bg-primary-dark). --}}
@once
    <style>
        .mod-navigation__bar--solid .mod-navigation__bar-link {
            transition: background-color 0.2s ease, color 0.1s ease;
        }

        .mod-navigation__bar--solid .mod-navigation__bar-link:hover,
        .mod-navigation__bar--solid .mod-navigation__bar-link:focus-visible {
            background-color: color-mix(in srgb, var(--color--primary-contrast) 14%, var(--color--primary));
            color: var(--color--primary-contrast);
        }

        .mod-navigation__bar--solid .mod-navigation__bar-link:visited,
        .mod-navigation__bar--solid .mod-navigation__bar-link:visited:hover {
            color: var(--color--primary-contrast);
        }

        .mod-navigation__bar-link--outline:hover,
        .mod-navigation__bar-link--outline:focus-visible {
            background-color: color-mix(in srgb, var(--color--primary) 6%, transparent);
            transition: background-color 0.2s ease;
        }
    </style>
@endonce
