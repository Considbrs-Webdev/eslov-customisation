<?php

namespace EslovCustomisation\PostsFiltering;

/**
 * Builds WP_Query tax_query arrays from Eslöv filter rules.
 */
class TaxQueryBuilder
{
    /**
     * @param list<array{taxonomy: string, operator: string, term_id: int}> $rules
     * @return list<array<string, mixed>>
     */
    public static function build(array $rules): array
    {
        $taxQuery = [];

        foreach ($rules as $rule) {
            $taxQuery[] = [
                'taxonomy' => $rule['taxonomy'],
                'field' => 'id',
                'terms' => $rule['term_id'],
                'operator' => $rule['operator'],
            ];
        }

        return $taxQuery;
    }
}
