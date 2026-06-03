@if (!empty($items))
    <ul class="mod-navigation__list u-unlist">
        @foreach ($items as $item)
            @php
                $icon = \EslovCustomisation\Modules\Navigation\IconNormalizer::forComponent($item['icon'] ?? null);
            @endphp
            <li class="mod-navigation__list-item u-margin__bottom--05">
                <a href="{{ $item['href'] ?? '#' }}" class="mod-navigation__list-link u-display--flex u-align-items--center u-gap__1">
                    @if ($icon)
                        @icon($icon)
                        @endicon
                    @endif
                    <span class="u-text-decoration--underline">{{ $item['title'] ?? '' }}</span>
                </a>
            </li>
        @endforeach
    </ul>
@endif
