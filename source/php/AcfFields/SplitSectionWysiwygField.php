<?php

/**
 * Adds optional WYSIWYG editor for Split Section text content.
 */

namespace EslovCustomisation\AcfFields;

class SplitSectionWysiwygField
{
    private const PARENT_GROUP = 'group_599fddb1da69a';

    private const TOGGLE_FIELD_KEY = 'field_eslov_use_wysiwyg_split';

    private const WYSIWYG_FIELD_KEY = 'field_eslov_wysiwyg_text_split';

    private const TEXTAREA_FIELD_KEY = 'field_60d1a8040b829';

    public function __construct()
    {
        add_action('acf/init', function () {
            add_action('init', [$this, 'registerFields'], 20);
            if (did_action('init')) {
                $this->registerFields();
            }
        });

        add_filter('acf/load_field/key=' . self::TEXTAREA_FIELD_KEY, [$this, 'hideTextareaWhenWysiwygEnabled']);
        add_filter('acf/load_value/key=' . self::TEXTAREA_FIELD_KEY, [$this, 'useWysiwygContent'], 10, 3);
    }

    /**
     * Register the toggle checkbox and WYSIWYG field.
     */
    public function registerFields(): void
    {
        if (!function_exists('acf_add_local_field') || !function_exists('acf_get_field_group')) {
            return;
        }

        if (!acf_get_field_group(self::PARENT_GROUP)) {
            return;
        }

        acf_add_local_field([
            'parent'        => self::PARENT_GROUP,
            'key'           => self::TOGGLE_FIELD_KEY,
            'label'         => '',
            'name'          => 'use_wysiwyg',
            'type'          => 'true_false',
            'message'       => __('Use rich text editor', 'eslov-customisation'),
            'instructions'  => '',
            'required'      => 0,
            'default_value' => 0,
            'ui'            => 1,
            'menu_order'    => 1,
            'wrapper'       => ['width' => '75'],
        ]);

        acf_add_local_field([
            'parent'           => self::PARENT_GROUP,
            'key'              => self::WYSIWYG_FIELD_KEY,
            'label'            => __('Text', 'eslov-customisation'),
            'name'             => 'wysiwyg_text',
            'type'             => 'wysiwyg',
            'instructions'     => '',
            'required'         => 0,
            'tabs'             => 'all',
            'toolbar'          => 'basic',
            'media_upload'     => 0,
            'delay'            => 0,
            'menu_order'       => 2,
            'wrapper'          => ['width' => '75'],
            'conditional_logic' => [
                [
                    [
                        'field'    => self::TOGGLE_FIELD_KEY,
                        'operator' => '==',
                        'value'    => '1',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Use WYSIWYG content for the text field value when toggle is enabled (frontend).
     *
     * @param mixed $value
     * @param int|string $postId
     * @param array<string, mixed> $field
     * @return mixed
     */
    public function useWysiwygContent(mixed $value, int|string $postId, array $field): mixed
    {
        if (is_admin()) {
            return $value;
        }

        if (!is_numeric($postId)) {
            return $value;
        }

        if (get_post_type((int) $postId) !== 'mod-section-split') {
            return $value;
        }

        if (!get_field('use_wysiwyg', $postId)) {
            return $value;
        }

        $wysiwygContent = get_field('wysiwyg_text', $postId);

        return $wysiwygContent ?: $value;
    }

    /**
     * Add conditional logic to hide original textarea when WYSIWYG is enabled.
     *
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    public function hideTextareaWhenWysiwygEnabled(array $field): array
    {
        $existingLogic = $field['conditional_logic'] ?? [];

        $wysiwygCondition = [
            [
                'field'    => self::TOGGLE_FIELD_KEY,
                'operator' => '!=',
                'value'    => '1',
            ],
        ];

        if (empty($existingLogic)) {
            $field['conditional_logic'] = [$wysiwygCondition];
        } else {
            foreach ($existingLogic as &$group) {
                $group[] = [
                    'field'    => self::TOGGLE_FIELD_KEY,
                    'operator' => '!=',
                    'value'    => '1',
                ];
            }
            $field['conditional_logic'] = $existingLogic;
        }

        return $field;
    }
}
