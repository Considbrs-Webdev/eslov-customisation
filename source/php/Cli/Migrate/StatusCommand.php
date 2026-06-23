<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\MigrationRegistry;

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

        \WP_CLI\Utils\format_items(
            'table',
            MigrationRegistry::all(),
            ['command', 'description', 'status'],
        );
    }

    /**
     * @param array<string, mixed> $assocArgs
     */
    public function runTask(array $assocArgs): void
    {
        // Not used — status is read-only.
    }
}
