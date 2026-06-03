@switch($tree_style ?? 'standard')
    @case('highlighted')
        @include('navigation.tree.highlighted')
        @break
    @default
        @include('navigation.tree.standard')
@endswitch
