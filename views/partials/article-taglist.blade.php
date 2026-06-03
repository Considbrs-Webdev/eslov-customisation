@if (!empty($tags))
    <div class="c-tags u-margin__bottom--2 eslov-article-taglist">
        @foreach ($tags as $tag)
            @if (!empty($tag['href']))
                <a class="c-tags__tag" href="{{ esc_url($tag['href']) }}">
                    {{ $tag['label'] }}
                </a>
            @else
                <span class="c-tags__tag">
                    {{ $tag['label'] }}
                </span>
            @endif
        @endforeach
    </div>
@endif
