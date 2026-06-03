@include('navigation.bar.partials.interaction-styles')

@switch($bar_style ?? 'outline')
    @case('solid')
        @include('navigation.bar.solid')
        @break
    @default
        @include('navigation.bar.outline')
@endswitch
