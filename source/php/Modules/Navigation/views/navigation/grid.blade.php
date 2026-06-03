@switch($grid_style ?? 'default')
    @case('blocks')
        @include('navigation.grid.blocks')
        @break
    @default
        @include('navigation.grid.default')
@endswitch
