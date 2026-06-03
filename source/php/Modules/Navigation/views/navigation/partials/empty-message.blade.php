@if (!empty($empty_message) && empty($items))
    <div class="mod-navigation__empty u-margin__top--2">
        {!! wp_kses_post($empty_message) !!}
    </div>
@endif
