<?php

namespace EslovCustomisation\AcfFields;

/**
 * Restores LTS municipio-extended redirect_to link field on taxonomy terms.
 */
class TermRedirectToField
{
    private const PARENT_GROUP = 'group_63e6002cc129c';

    private const FIELD_KEY = 'field_redirect_to';

    public function __construct()
    {
        add_action('acf/init', function () {
            add_action('init', [$this, 'register'], 20);

            if (did_action('init')) {
                $this->register();
            }
        });
    }

    public function register(): void
    {
        if (!function_exists('acf_add_local_field') || !function_exists('acf_get_field_group')) {
            return;
        }

        if (!acf_get_field_group(self::PARENT_GROUP)) {
            return;
        }

        if (function_exists('acf_get_field') && acf_get_field(self::FIELD_KEY)) {
            return;
        }

        acf_add_local_field([
            'parent' => self::PARENT_GROUP,
            'key' => self::FIELD_KEY,
            'label' => __('Redirect to', 'eslov-customisation'),
            'name' => 'redirect_to',
            'type' => 'link',
            'instructions' => __(
                'Optional URL when the taxonomy tag is clicked on singular posts.',
                'eslov-customisation',
            ),
            'required' => 0,
            'return_format' => 'array',
        ]);
    }
}
