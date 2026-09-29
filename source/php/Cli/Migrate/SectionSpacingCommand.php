<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\SectionSpacingMigrator;

class SectionSpacingCommand extends AbstractMigrateCommand
{
    protected const SUPPORTS_FORCE_FLAG = true;

    /**
     * Set missing section Spacing Top/Bottom meta to the Municipio default (on).
     *
     * Does not change module_layout_remove_spacing_below (LTS module gap).
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database.
     *
     * [--force]
     * : Re-apply spacing_top/spacing_bottom=1 even when a value is already stored.
     *
     * [--post-id=<id>]
     * : Limit migration to a single section module on the current site.
     *
     * [--network]
     * : Run on every site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate section-spacing --dry-run
     *     wp eslov migrate section-spacing --network
     *     wp eslov migrate section-spacing --post-id=586627
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

        $result = (new SectionSpacingMigrator($this->dryRun, $force, $this->postId))->migrate();
        $this->logResult($result);
    }
}
