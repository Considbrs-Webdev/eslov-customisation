<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\SectionTextAutopMigrator;

class SectionTextAutopCommand extends AbstractMigrateCommand
{
    /**
     * Persist <p> tags in split/featured/card section Text where blank lines
     * were previously turned into paragraphs only at display time.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database.
     *
     * [--post-id=<id>]
     * : Limit migration to a single section module on the current site.
     *
     * [--network]
     * : Run on every site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate section-text-autop --dry-run
     *     wp eslov migrate section-text-autop --network
     *     wp eslov migrate section-text-autop --post-id=498820
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
        $result = (new SectionTextAutopMigrator($this->dryRun, $this->postId))->migrate();
        $this->logResult($result);
    }
}
