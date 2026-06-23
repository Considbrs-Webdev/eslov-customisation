<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\ThemeModsMigrator;

class ThemeModsCommand extends AbstractMigrateCommand
{
    /**
     * Apply Municipio theme mod defaults missing from the LTS import.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database.
     *
     * [--force]
     * : Re-apply theme mods even when already set to the desired value.
     *
     * [--network]
     * : Run on every site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate theme-mods --dry-run
     *     wp eslov migrate theme-mods
     *     wp eslov migrate theme-mods --force
     *     wp eslov migrate theme-mods --network
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

        $result = (new ThemeModsMigrator($this->dryRun, $force))->migrate();
        $this->logResult($result);
    }
}
