<div class="u-margin__top--3">
    @button([
        'href' => $href,
        'text' => sprintf(__('Till startsidan för %s', 'eslov-customisation'), esc_html($title)),
        'style' => 'outlined',
        'color' => 'primary',
        'icon' => 'arrow_back',
        'reversePositions' => true,
    ])
    @endbutton
</div>
