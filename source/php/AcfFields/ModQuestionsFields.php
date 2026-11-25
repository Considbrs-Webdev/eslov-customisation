<?php

namespace EslovCustomisation\AcfFields;

class ModQuestionsFields
{
    public function __construct()
    {
        add_action('acf/init', [$this, 'register'], 5);
    }

    public function register(): void
    {
        if (!function_exists('acf_add_local_field_group') || !function_exists('acf_get_field_group')) {
            return;
        }

        if (acf_get_field_group('group_mod_questions')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_mod_questions',
            'title' => __('Questions and answers', 'eslov-customisation'),
            'fields' => [
                [
                    'key' => 'field_rekai_question_count',
                    'label' => __('Number of questions', 'eslov-customisation'),
                    'name' => 'rekai_question_count',
                    'type' => 'number',
                    'instructions' => __('Only questions approved in Rek.ai are shown.', 'eslov-customisation'),
                    'required' => 0,
                    'default_value' => 5,
                    'min' => 1,
                    'max' => 8,
                    'step' => 1,
                ],
                [
                    'key' => 'field_rekai_question_scope',
                    'label' => __('Show questions from', 'eslov-customisation'),
                    'name' => 'rekai_question_scope',
                    'type' => 'radio',
                    'instructions' => __('Questions from this page and pages below it.', 'eslov-customisation'),
                    'required' => 0,
                    'choices' => [
                        'current' => __('This page', 'eslov-customisation'),
                        'section' => __('This section', 'eslov-customisation'),
                    ],
                    'default_value' => 'current',
                    'return_format' => 'value',
                    'layout' => 'vertical',
                ],
                [
                    'key' => 'field_rekai_question_root_level',
                    'label' => __('URL level', 'eslov-customisation'),
                    'name' => 'rekai_question_root_level',
                    'type' => 'number',
                    'instructions' => __(
                        '1 is the first segment of the URL path, same as Rek.ai rootpathlevel.',
                        'eslov-customisation',
                    ),
                    'required' => 0,
                    'default_value' => 1,
                    'min' => 1,
                    'max' => 6,
                    'step' => 1,
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'field_rekai_question_scope',
                                'operator' => '==',
                                'value' => 'section',
                            ],
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'mod-questions',
                    ],
                ],
            ],
        ]);
    }
}
