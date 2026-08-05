<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\ManualInputDataRepairMigrator;

class ManualInputDataRepairCommand extends AbstractMigrateCommand
{
    /**
     * Repair mod-manualinput modules where leftover LTS data_* was not fully migrated to manual_inputs.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database.
     *
     * [--post-id=<id>]
     * : Repair a single mod-manualinput module.
     *
     * [--network]
     * : Run on every site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate manual-input-data-repair --dry-run
     *     wp eslov migrate manual-input-data-repair
     *     wp eslov migrate manual-input-data-repair --post-id=29223
     *     wp eslov migrate manual-input-data-repair --network
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
        $result = (new ManualInputDataRepairMigrator($this->dryRun, $this->postId))->migrate();
        $this->logResult($result);
    }
}
