<?php

namespace EslovCustomisation\Modules\Questions;

class Questions extends \Modularity\Module
{
    public $slug = 'questions';

    public $supports = [];

    public function init(): void
    {
        $this->templateDir = ESLOV_CUSTOMISATION_PATH . 'source/php/Modules/Questions/views/';

        $this->nameSingular = __(
            'Questions and answers',
            'eslov-customisation',
        );
        $this->namePlural = __(
            'Questions and answers',
            'eslov-customisation',
        );
        $this->description = __(
            'Shows approved Rek.ai questions and answers.',
            'eslov-customisation',
        );

        $iconSvg = '<svg width="24" height="24" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 15.25a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5Zm1.2-4.55-.35.18V14h-1.7v-1.7c0-.7.4-1.32 1.02-1.62.95-.46 1.58-1.05 1.58-1.93 0-1.15-.95-1.9-2.2-1.9-1.2 0-2.05.62-2.4 1.62l-1.55-.62C8.2 6.55 9.7 5.25 12 5.25c2.15 0 3.85 1.35 3.85 3.4 0 1.55-1.05 2.55-2.65 3.05Z" fill="#212121"/></svg>';
        $this->icon = 'data:image/svg+xml;base64,' . base64_encode($iconSvg);
    }

    public function template(): string
    {
        return 'mod-questions.blade.php';
    }

    /**
     * View data for the questions module.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $fields = $this->getFields() ?: [];
        $scope = ($fields['rekai_question_scope'] ?? 'current') === 'section' ? 'section' : 'current';
        $count = (int) ($fields['rekai_question_count'] ?? 5);
        $level = (int) ($fields['rekai_question_root_level'] ?? 1);

        $params = [
            'entitytype' => 'rekai-qna',
            'nrofhits' => max(1, min(8, $count > 0 ? $count : 5)),
            'hideanswerlinktopageifsamepage' => true,
        ];

        if ($scope === 'section') {
            $params['userootpath'] = true;
            $params['rootpathlevel'] = max(1, min(6, $level > 0 ? $level : 1));
        } else {
            $params['currentpagequestions'] = true;
        }

        return [
            'rekaiEnabled' => $this->isRekAiEnabled(),
            'showEditorNotice' => is_user_logged_in() && current_user_can('edit_posts'),
            'editorPreview' => is_admin(),
            'editorHint' => __('Only questions approved in Rek.ai are shown.', 'eslov-customisation'),
            'mountId' => function_exists('wp_unique_id') ? wp_unique_id('rekai-qna-') : uniqid('rekai-qna-'),
            'readMoreLabel' => __('Read more', 'eslov-customisation'),
            'notice' => __(
                'Rek.ai is not enabled on this site. Questions and answers stay hidden for visitors.',
                'eslov-customisation',
            ),
            'predictParams' => $params,
        ];
    }

    /**
     * Rek.ai is on for this blog when the recommend plugin has a script URL.
     */
    private function isRekAiEnabled(): bool
    {
        if (!function_exists('get_field')) {
            return false;
        }

        if (!(bool) get_field('rekai_enable', 'options')) {
            return false;
        }

        $scriptUrl = get_field('rekai_script_url', 'options');

        return is_string($scriptUrl) && $scriptUrl !== '';
    }
}
