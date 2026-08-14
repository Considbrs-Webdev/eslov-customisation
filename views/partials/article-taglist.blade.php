@if (!empty($tags))
    <ul class="eslov-article-taglist unlist">
        @foreach ($tags as $tag)
            <li
                class="eslov-article-taglist__item{{ !empty($tag['color']) ? ' eslov-article-taglist__item--colored' : '' }}"
                @if (!empty($tag['color']))
                    style="--eslov-tag-color: {{ esc_attr($tag['color']) }};"
                @endif
            >
                @if (!empty($tag['href']))
                    <a class="eslov-article-taglist__tag" href="{{ esc_url($tag['href']) }}">
                        {{ $tag['label'] }}
                    </a>
                @else
                    <span class="eslov-article-taglist__tag">
                        {{ $tag['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
@endif
