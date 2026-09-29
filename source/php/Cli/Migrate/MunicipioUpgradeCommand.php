<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\MunicipioUpgradeRunner;

class MunicipioUpgradeCommand extends AbstractMigrateCommand
{
    /**
     * Run pending Municipio theme database upgrades on the current site.
     *
     * Upstream upgrades (including V41 token mapping) only run on the `wp`
     * hook, so WP-CLI never triggers them. Run this before design-tokens.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned upgrades without running Municipio upgrade.
     *
     * [--network]
     * : Run on every site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate municipio-upgrade --dry-run
     *     wp eslov migrate municipio-upgrade
     *     wp eslov migrate municipio-upgrade --network
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
        $result = (new MunicipioUpgradeRunner($this->dryRun))->migrate();
        $this->logResult($result);
    }
}
