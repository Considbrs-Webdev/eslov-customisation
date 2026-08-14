<?php

namespace EslovCustomisation\Support;

/**
 * Theme mod keys and resolution for singular taxonomy taglists (LTS municipio-extended).
 */
class SingularTaxonomySettings
{
    public const PLACEMENT_UNDER_HEADER = 'under_header';

    public const PLACEMENT_AFTER_CONTENT = 'after_content';

    public static function taxonomiesThemeModKey(string $postType): string
    {
        return 'municipio_customizer_panel_content_types_' . $postType . '_taxonomies';
    }

    public static function placementThemeModKey(string $postType): string
    {
        return 'municipio_customizer_panel_content_types_' . $postType . '_taxonomy_placement';
    }

    /**
     * @return \WP_Post_Type[]
     */
    public static function getPostTypesWithPublicTaxonomies(): array
    {
        $result = [];

        foreach (get_post_types(['public' => true], 'objects') as $postType) {
            if (self::getPublicTaxonomyChoices($postType->name) !== []) {
                $result[$postType->name] = $postType;
            }
        }

        return $result;
    }

    /**
     * @return array<string, string> slug => label
     */
    public static function getPublicTaxonomyChoices(string $postType): array
    {
        $choices = [];

        foreach (get_object_taxonomies($postType, 'objects') as $taxonomy) {
            if (!$taxonomy->public) {
                continue;
            }

            $choices[$taxonomy->name] = $taxonomy->label;
        }

        return $choices;
    }

    public static function getPlacement(string $postType): string
    {
        $placement = get_theme_mod(self::placementThemeModKey($postType));

        if (
            is_string($placement)
            && in_array($placement, [self::PLACEMENT_UNDER_HEADER, self::PLACEMENT_AFTER_CONTENT], true)
        ) {
            return $placement;
        }

        return self::PLACEMENT_UNDER_HEADER;
    }

    /**
     * Unset theme mod → all public taxonomies (LTS Kirki default).
     * Empty saved array → show none.
     *
     * @return string[]
     */
    public static function getSelectedTaxonomies(string $postType): array
    {
        $stored = get_theme_mod(self::taxonomiesThemeModKey($postType), false);

        if ($stored === false) {
            return array_keys(self::getPublicTaxonomyChoices($postType));
        }

        if (!is_array($stored)) {
            return array_keys(self::getPublicTaxonomyChoices($postType));
        }

        if ($stored === []) {
            return [];
        }

        return array_values(array_filter($stored, 'is_string'));
    }

    /**
     * @param mixed $value
     * @return string[]
     */
    public static function sanitizeTaxonomySelection(string $postType, $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $valid = array_keys(self::getPublicTaxonomyChoices($postType));

        return array_values(array_intersect($value, $valid));
    }

    /**
     * @param mixed $value
     */
    public static function sanitizePlacement($value): string
    {
        return $value === self::PLACEMENT_AFTER_CONTENT
            ? self::PLACEMENT_AFTER_CONTENT
            : self::PLACEMENT_UNDER_HEADER;
    }
}
