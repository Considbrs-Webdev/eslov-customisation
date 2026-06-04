<?php

namespace EslovCustomisation\Modules\Navigation;

class Navigation extends \Modularity\Module
{
    public $slug = 'navigation';

    public $supports = [];

    public function init(): void
    {
        $this->templateDir = ESLOV_CUSTOMISATION_PATH . 'source/php/Modules/Navigation/views/';

        $this->nameSingular = _x(
            'Navigation',
            'Post Type Singular Name',
            'eslov-customisation',
        );
        $this->namePlural = _x(
            'Navigation modules',
            'Post Type General Name',
            'eslov-customisation',
        );
        $this->description = __(
            'Outputs a menu or manually selected links',
            'eslov-customisation',
        );

        $iconSvg = '<svg width="24" height="24" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.001c5.524 0 10 4.477 10 10s-4.476 10-10 10c-5.522 0-10-4.477-10-10s4.478-10 10-10Zm.781 5.469-.084-.073a.75.75 0 0 0-.883-.007l-.094.08-.072.084a.75.75 0 0 0-.007.883l.08.094 2.719 2.72H7.75l-.102.006a.75.75 0 0 0-.641.642L7 12l.007.102a.75.75 0 0 0 .641.641l.102.007h6.69l-2.72 2.72-.073.085a.75.75 0 0 0 1.05 1.05l.083-.073 4.002-4 .072-.085a.75.75 0 0 0 .008-.882l-.08-.094-4-4.001-.085-.073.084.073Z" fill="#212121"/></svg>';
        $this->icon = 'data:image/svg+xml;base64,' . base64_encode($iconSvg);
    }

    public function template(): string
    {
        return 'mod-navigation.blade.php';
    }

    /**
     * Enqueue module CSS only when this module is rendered on the page.
     */
    public function style(): void
    {
        $this->wpEnqueue?->add(
            'css/mod-navigation.css',
            [],
            defined('ESLOV_CUSTOMISATION_VERSION') ? ESLOV_CUSTOMISATION_VERSION : '0.1.0',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $data = array_merge(
            (new KirkiStyleReader())->read(),
            $this->stripModuleFieldKeys($this->getFields() ?: []),
        );

        $resolver = new ItemResolver($this->ID, $this->slug);
        $data['items'] = $resolver->resolve(fn (string $field) => $this->getModuleField($field));

        $showIfEmpty = !empty($data['show_if_empty']);
        $data['hideIfEmpty'] = apply_filters(
            'mx/mod_navigation/hide_if_empty',
            !$showIfEmpty,
            $this->slug,
            $this->ID,
            $data,
        );

        return $data;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function stripModuleFieldKeys(array $fields): array
    {
        $prefix = 'mod_navigation_';
        $stripped = [];

        foreach ($fields as $key => $value) {
            if (str_starts_with((string) $key, $prefix)) {
                $stripped[substr((string) $key, strlen($prefix))] = $value;
            } else {
                $stripped[$key] = $value;
            }
        }

        return $stripped;
    }

    private function getModuleField(string $field): mixed
    {
        if (function_exists('get_field') && $this->ID) {
            return get_field($field, $this->ID);
        }

        $fields = $this->getFields() ?: [];

        return $fields[$field] ?? null;
    }
}
