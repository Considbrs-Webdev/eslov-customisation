<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;

class StatusCommand extends AbstractMigrateCommand
{
    /**
     * List registered Eslöv migration tasks.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : No-op flag for consistency with other migrate commands.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate status
     *
     * @param array<int, string> $args
     * @param array<string, mixed> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $this->parseMigrateFlags($assocArgs);
        $this->logDryRunNotice();

        $registry = [
            [
                'command' => 'eslov migrate status',
                'description' => 'List migration tasks (this command)',
                'status' => 'scaffold',
            ],
            [
                'command' => 'eslov migrate meta-keys',
                'description' => 'Rewrite legacy post meta keys',
                'status' => 'planned',
            ],
            [
                'command' => 'eslov migrate widgets',
                'description' => 'Classic modularity-module widgets → block shortcode widgets',
                'status' => 'ready',
            ],
            [
                'command' => 'eslov migrate mod-posts-taxonomy-display',
                'description' => 'LTS taxonomy_selection_in_fields → taxonomy_display on mod-posts',
                'status' => 'ready',
            ],
            [
                'command' => 'eslov migrate design-tokens',
                'description' => 'Kirki typography_button / search_form_shape → Design Builder tokens',
                'status' => 'ready',
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

        \WP_CLI\Utils\format_items('table', $registry, ['command', 'description', 'status']);
    }
}
