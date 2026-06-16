<?php

namespace EslovCustomisation\PostsFiltering;

/**
 * Normalizes mod-posts ACF fields into taxonomy filter rules.
 */
class FilterRulesResolver
{
    /**
     * @param array<string, mixed> $fields
     * @return list<array{taxonomy: string, operator: string, term_id: int}>
     */
    public static function fromFields(array $fields, ?int $modulePostId = null): array
    {
        $rows = $fields['mod_posts_filtering'] ?? null;

        if (is_array($rows) && $rows !== []) {
            $rules = [];

            foreach ($rows as $row) {
                $rule = self::normalizeRow($row);

                if ($rule !== null) {
                    $rules[] = $rule;
                }
            }

            if ($rules !== []) {
                return $rules;
            }
        }

        if ($modulePostId !== null && $modulePostId > 0) {
            $metaRules = self::fromPostMeta($modulePostId);

            if ($metaRules !== []) {
                return $metaRules;
            }
        }

        if (!self::isFilteringEnabled($fields)) {
            return [];
        }

        return [];
    }

    /**
     * @return list<array{taxonomy: string, operator: string, term_id: int}>
     */
    public static function fromPostMeta(int $modulePostId): array
    {
        $rowCount = (int) get_post_meta($modulePostId, 'mod_posts_filtering', true);

        if ($rowCount <= 0) {
            $rowCount = self::countRepeaterRowsInMeta($modulePostId);
        }

        if ($rowCount <= 0) {
            return [];
        }

        $rules = [];

        for ($index = 0; $index < $rowCount; $index++) {
            $taxonomy = (string) get_post_meta($modulePostId, "mod_posts_filtering_{$index}_taxonomy", true);

            if ($taxonomy === '') {
                continue;
            }

            $operator = (string) get_post_meta($modulePostId, "mod_posts_filtering_{$index}_operator", true);
            $termId = (int) get_post_meta($modulePostId, "mod_posts_filtering_{$index}_term_{$taxonomy}", true);

            if ($termId <= 0) {
                continue;
            }

            if (!in_array($operator, ['IN', 'NOT IN'], true)) {
                $operator = 'IN';
            }

            $rules[] = [
                'taxonomy' => $taxonomy,
                'operator' => $operator,
                'term_id' => $termId,
            ];
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, int>
     */
    public static function archiveFiltersFromFields(array $fields, ?int $modulePostId = null): array
    {
        $filters = [];

        foreach (self::fromFields($fields, $modulePostId) as $rule) {
            $filters['filter[' . $rule['taxonomy'] . ']'] = $rule['term_id'];
        }

        return $filters;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function isFilteringEnabled(array $fields): bool
    {
        $toggle = $fields['posts_taxonomy_filter'] ?? false;

        return $toggle === true || $toggle === 1 || $toggle === '1';
    }

    /**
     * @param mixed $row
     * @return array{taxonomy: string, operator: string, term_id: int}|null
     */
    private static function normalizeRow(mixed $row): ?array
    {
        if (is_object($row)) {
            $row = (array) $row;
        }

        if (!is_array($row)) {
            return null;
        }

        $taxonomy = isset($row['taxonomy']) ? (string) $row['taxonomy'] : '';

        if ($taxonomy === '') {
            return null;
        }

        $termKey = 'term_' . $taxonomy;
        $termId = isset($row[$termKey]) ? (int) $row[$termKey] : 0;

        if ($termId <= 0) {
            return null;
        }

        $operator = isset($row['operator']) ? (string) $row['operator'] : 'IN';

        if (!in_array($operator, ['IN', 'NOT IN'], true)) {
            $operator = 'IN';
        }

        return [
            'taxonomy' => $taxonomy,
            'operator' => $operator,
            'term_id' => $termId,
        ];
    }

    private static function countRepeaterRowsInMeta(int $modulePostId): int
    {
        $meta = get_post_meta($modulePostId);
        $count = 0;

        foreach (array_keys($meta) as $metaKey) {
            if (preg_match('/^mod_posts_filtering_\d+_taxonomy$/', (string) $metaKey) === 1) {
                $count++;
            }
        }

        return $count;
    }
}
