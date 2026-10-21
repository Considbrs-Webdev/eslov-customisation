<?php

declare(strict_types=1);

namespace EslovCustomisation\Customisations;

use WP_User;

/**
 * Lets wp-admin finish rendering when a submenu capability is null.
 *
 * @upstream-shim id=municipio-custom-css-cap-null
 * @upstream-repo municipio (library/Customizer/CustomCssCapabilities.php)
 * @upstream-broken CustomCssCapabilities::allowCustomCssEditing() type-hints argument 2 as string. wp-admin/menu-header.php calls current_user_can() with a submenu capability, and a null value is passed straight into map_meta_cap. The TypeError stops the admin after the menu is printed. Introduced in e6e1476bb6 (2026-08-31, "feat: allow editors to edit custom css"). Still present in municipio 7.70.0.
 * @upstream-fix-needed Accept null (mixed or ?string) and return the original capabilities unless the capability is edit_css.
 * @remove-when Deployed Municipio no longer type-hints allowCustomCssEditing()'s $capability as string. Grep CustomCssCapabilities.php. Not fixed as of municipio 7.70.0. Then delete this class and its App.php registration.
 * @verify-removal Open wp-admin on the main site (plugins.php). The page body renders and the log has no TypeError from CustomCssCapabilities.
 */
class CustomCssCapability
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_ROLES = [
        'administrator',
        'editor',
    ];

    public function __construct()
    {
        add_action('after_setup_theme', [$this, 'replaceStrictFilter'], 100);
        add_action('init', [$this, 'replaceStrictFilter'], 999);
    }

    /**
     * Drop Municipio's strict callback and keep the same edit_css grant.
     */
    public function replaceStrictFilter(): void
    {
        if (!$this->removeMunicipioFilter()) {
            return;
        }

        if (has_filter('map_meta_cap', [$this, 'allowCustomCssEditing']) !== false) {
            return;
        }

        add_filter('map_meta_cap', [$this, 'allowCustomCssEditing'], 10, 4);
    }

    /**
     * Allow administrators and editors to edit custom CSS.
     *
     * A null capability is left unchanged so current_user_can(null) can finish.
     *
     * @param mixed $requiredCapabilities
     * @param mixed $capability
     * @param mixed $userId
     * @return array<int, string>
     */
    public function allowCustomCssEditing(mixed $requiredCapabilities, mixed $capability, mixed $userId): array
    {
        $requiredCapabilities = is_array($requiredCapabilities) ? $requiredCapabilities : [];

        if ($capability !== 'edit_css' || !is_numeric($userId) || !$this->userHasAllowedRole((int) $userId)) {
            return $requiredCapabilities;
        }

        return ['edit_theme_options'];
    }

    private function userHasAllowedRole(int $userId): bool
    {
        $user = get_userdata($userId);

        if (!$user instanceof WP_User) {
            return false;
        }

        return array_intersect(self::ALLOWED_ROLES, (array) $user->roles) !== [];
    }

    private function removeMunicipioFilter(): bool
    {
        global $wp_filter;

        if (!isset($wp_filter['map_meta_cap']) || !is_object($wp_filter['map_meta_cap'])) {
            return false;
        }

        $removals = [];

        foreach ($wp_filter['map_meta_cap']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $function = $callback['function'] ?? null;

                if (!is_array($function) || ($function[1] ?? null) !== 'allowCustomCssEditing') {
                    continue;
                }

                $target = $function[0];
                $className = is_object($target) ? $target::class : (is_string($target) ? $target : '');

                if ($className === 'Municipio\\Customizer\\CustomCssCapabilities') {
                    $removals[] = [$function, (int) $priority];
                }
            }
        }

        foreach ($removals as [$function, $priority]) {
            remove_filter('map_meta_cap', $function, $priority);
        }

        return $removals !== [];
    }
}
