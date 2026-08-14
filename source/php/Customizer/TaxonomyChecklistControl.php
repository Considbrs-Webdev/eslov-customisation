<?php

namespace EslovCustomisation\Customizer;

/**
 * Multicheck taxonomy picker for WP Customizer (replaces Kirki multicheck).
 */
class TaxonomyChecklistControl extends \WP_Customize_Control
{
    /**
     * @var string
     */
    public $type = 'eslov_taxonomy_checklist';

    /**
     * @var array<string, string>
     */
    public $choices = [];

    protected function render_content(): void
    {
        if ($this->label !== '') {
            echo '<span class="customize-control-title">' . esc_html($this->label) . '</span>';
        }

        if ($this->description !== '') {
            echo '<span class="customize-control-description">' . esc_html($this->description) . '</span>';
        }

        if ($this->choices === []) {
            echo '<p>' . esc_html__('No public taxonomies for this post type.', 'eslov-customisation') . '</p>';

            return;
        }

        $selected = $this->value();
        if ($selected === false || $selected === null) {
            $selected = array_keys($this->choices);
        } elseif (!is_array($selected)) {
            $selected = [];
        }

        echo '<ul class="eslov-taxonomy-checklist">';

        foreach ($this->choices as $slug => $label) {
            $checked = in_array($slug, $selected, true);
            $inputId = esc_attr($this->id . '-' . $slug);

            printf(
                '<li><label for="%1$s"><input id="%1$s" type="checkbox" value="%2$s" %3$s /> %4$s</label></li>',
                $inputId,
                esc_attr($slug),
                checked($checked, true, false),
                esc_html($label),
            );
        }

        echo '</ul>';
    }
}
