<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\ModularityUpgradeRunner;

class ModularityUpgradeCommand extends AbstractMigrateCommand
{
    /**
     * Run upstream Modularity database upgrade on the current site.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned upgrades without running modularity upgrade.
     *
     * [--network]
     * : Run on every site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate modularity-upgrade --dry-run
     *     wp eslov migrate modularity-upgrade
     *     wp eslov migrate modularity-upgrade --network
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
        $result = (new ModularityUpgradeRunner($this->dryRun))->migrate();
        $this->logResult($result);
    }
}
