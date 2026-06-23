<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\ModPostsTaxonomyDisplayMigrator;

class ModPostsTaxonomyDisplayCommand extends AbstractMigrateCommand
{
    /**
     * Migrate LTS mod-posts taxonomy tag config to Municipio taxonomy_display.
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
     * : Overwrite existing taxonomy_display values.
     *
     * [--network]
     * : Run on every site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate mod-posts-taxonomy-display --dry-run
     *     wp eslov migrate mod-posts-taxonomy-display
     *     wp eslov migrate mod-posts-taxonomy-display --post-id=516075
     *     wp eslov migrate mod-posts-taxonomy-display --network
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
        $result = (new ModPostsTaxonomyDisplayMigrator($this->dryRun, $this->postId, $force))->migrate();
        $this->logResult($result);
    }
}
