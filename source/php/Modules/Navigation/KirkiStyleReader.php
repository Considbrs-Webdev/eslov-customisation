<?php

namespace EslovCustomisation\Modules\Navigation;

class KirkiStyleReader
{
    private const KIRKI_CONFIG = 'theme_custom';

    /**
     * @return array{bar_style: string, tree_style: string, grid_style: string}
     */
    public function read(): array
    {
        return [
            'bar_style' => $this->readSetting('mod_navigation_bar_style', 'outline'),
            'tree_style' => $this->readSetting('mod_navigation_tree_style', 'standard'),
            'grid_style' => $this->readSetting('mod_navigation_grid_style', 'default'),
        ];
    }

    private function readSetting(string $key, string $default): string
    {
        if (class_exists(\Kirki\Compatibility\Kirki::class)) {
            $value = \Kirki\Compatibility\Kirki::get_option(self::KIRKI_CONFIG, $key);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        $themeMod = get_theme_mod($key);
        if (is_string($themeMod) && $themeMod !== '') {
            return $themeMod;
        }

        return $default;
    }
}
