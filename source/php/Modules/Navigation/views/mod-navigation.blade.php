@if (!empty($items) || empty($hideIfEmpty))
<div class="mod-navigation mod-navigation--{{ $format ?? 'cards' }}">
    @include('navigation.partials.module-title')

    @switch($format ?? '')
        @case('list')
            @include('navigation.list')
            @break

        @case('grid')
            @include('navigation.grid')
            @break

        @case('bar')
            @include('navigation.bar')
            @break

        @case('tree')
            @include('navigation.tree')
            @break

        @case('cards')
            @include('navigation.cards')
            @break

        @case('buttons')
            @include('navigation.buttons')
            @break

        @case('inline')
            @include('navigation.inline')
            @break
    @endswitch

    @include('navigation.partials.empty-message')
</div>
@endif
