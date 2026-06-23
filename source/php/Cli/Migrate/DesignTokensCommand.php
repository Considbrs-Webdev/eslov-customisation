<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\DesignTokensMigrator;

class DesignTokensCommand extends AbstractMigrateCommand
{
    /**
     * Patch design-builder tokens from legacy Kirki settings and optional patch JSON.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database.
     *
     * [--force]
     * : Overwrite existing token values when they differ from legacy theme mods or patches.
     *
     * [--patches=<path>]
     * : Path to a patch JSON file (default: plugin config/styleguide-token-patches.json).
     *
     * [--network]
     * : Run on every site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate design-tokens --dry-run
     *     wp eslov migrate design-tokens
     *     wp eslov migrate design-tokens --force
     *     wp eslov migrate design-tokens --network
     *     wp eslov migrate design-tokens --patches=/tmp/overrides.json
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
        $patches = \WP_CLI\Utils\get_flag_value($assocArgs, 'patches', null);
        $patchPath = is_string($patches) && $patches !== '' ? $patches : null;

        $result = (new DesignTokensMigrator($this->dryRun, $force, $patchPath))->migrate();
        $this->logResult($result);
    }
}
