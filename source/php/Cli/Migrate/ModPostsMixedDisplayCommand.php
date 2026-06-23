<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\ModPostsMixedDisplayMigrator;

class ModPostsMixedDisplayCommand extends AbstractMigrateCommand
{
    /**
     * Migrate LTS "Kort och lista" mod-posts display (mixed) to Card + slider.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database.
     *
     * [--post-id=<id>]
     * : Migrate a single mod-posts module.
     *
     * [--force]
     * : Re-apply Card + slider settings even if already migrated.
     *
     * [--network]
     * : Run on every site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate mod-posts-mixed-display --dry-run
     *     wp eslov migrate mod-posts-mixed-display
     *     wp eslov migrate mod-posts-mixed-display --post-id=516075
     *     wp eslov migrate mod-posts-mixed-display --network
     *
     * @param array<int, string> $args
     * @param array<string, mixed> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $this->parseMigrateFlags($assocArgs);
        $this->prepareNetworkMigration($assocArgs);
        $this->logDryRunNotice();

        $this->executeAcrossSites($assocArgs, function () use ($assocArgs): void {
            $this->runTask($assocArgs);
        });
    }

    /**
     * @param array<string, mixed> $assocArgs
     */
    public function runTask(array $assocArgs): void
    {
        $force = \WP_CLI\Utils\get_flag_value($assocArgs, 'force', false);
        $result = (new ModPostsMixedDisplayMigrator($this->dryRun, $this->postId, $force))->migrate();
        $this->logResult($result);
    }
}
