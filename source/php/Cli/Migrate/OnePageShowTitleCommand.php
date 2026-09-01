<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\OnePageShowTitleMigrator;

class OnePageShowTitleCommand extends AbstractMigrateCommand
{
    protected const SUPPORTS_FORCE_FLAG = true;

    /**
     * Enable Municipio "Title (onepage)" on all one-page template pages.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database.
     *
     * [--force]
     * : Re-apply even when post_one_page_show_title is already enabled.
     *
     * [--post-id=<id>]
     * : Limit migration to a single one-page post on the current site.
     *
     * [--network]
     * : Run on every site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate one-page-show-title --dry-run
     *     wp eslov migrate one-page-show-title --network
     *     wp eslov migrate one-page-show-title --post-id=588812
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

        $result = (new OnePageShowTitleMigrator($this->dryRun, $force, $this->postId))->migrate();
        $this->logResult($result);
    }
}
