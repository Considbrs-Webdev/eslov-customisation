<?php

namespace EslovCustomisation\Migration;

class MigrationRegistry
{
    /**
     * @return array<int, array{command: string, description: string, status: string, run_order?: int}>
     */
    public static function all(): array
    {
        return [
            [
                'command' => 'eslov migrate status',
                'description' => 'List migration tasks (this command)',
                'status' => 'scaffold',
            ],
            [
                'command' => 'eslov migrate all',
                'description' => 'Run all ready migrations in order',
                'status' => 'ready',
            ],
            [
                'command' => 'eslov migrate modularity-upgrade',
                'description' => 'Upstream Modularity V5→V8 database upgrade (per site; use --network on multisite)',
                'status' => 'ready',
                'run_order' => 5,
            ],
            [
                'command' => 'eslov migrate widgets',
                'description' => 'Classic modularity-module widgets → block shortcode widgets',
                'status' => 'ready',
                'run_order' => 10,
            ],
            [
                'command' => 'eslov migrate mod-posts-taxonomy-display',
                'description' => 'LTS taxonomy_selection_in_fields → taxonomy_display on mod-posts',
                'status' => 'ready',
                'run_order' => 20,
            ],
            [
                'command' => 'eslov migrate mod-posts-mixed-display',
                'description' => 'LTS "Kort och lista" (mixed) → Card + slider on mod-posts',
                'status' => 'ready',
                'run_order' => 30,
            ],
            [
                'command' => 'eslov migrate theme-mods',
                'description' => 'Municipio theme mod corrections (vertical menu indent; nyheter collection→newsitem). Use --network on multisite.',
                'status' => 'ready',
                'run_order' => 40,
            ],
            [
                'command' => 'eslov migrate design-tokens',
                'description' => 'Kirki typography_button / search_form_shape → Design Builder tokens',
                'status' => 'ready',
                'run_order' => 50,
            ],
            [
                'command' => 'eslov migrate meta-keys',
                'description' => 'Rewrite legacy post meta keys',
                'status' => 'planned',
            ],
            [
                'command' => 'eslov migrate modules',
                'description' => 'Transform Modularity module slugs/JSON',
                'status' => 'planned',
            ],
            [
                'command' => 'eslov migrate options',
                'description' => 'Migrate site options',
                'status' => 'planned',
            ],
        ];
    }

    /**
     * Ready migrations that `wp eslov migrate all` should run, in order.
     *
     * @return array<int, array{command: string, description: string, status: string, run_order: int}>
     */
    public static function runnable(): array
    {
        $tasks = array_filter(
            self::all(),
            static fn (array $entry): bool => ($entry['status'] ?? '') === 'ready'
                && isset($entry['run_order']),
        );

        usort(
            $tasks,
            static fn (array $a, array $b): int => $a['run_order'] <=> $b['run_order'],
        );

        return array_values($tasks);
    }
}
