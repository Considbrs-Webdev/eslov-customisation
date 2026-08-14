<?php

/**
 * WP-CLI command to migrate Split Section textarea to WYSIWYG.
 */

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\SplitSectionWysiwygMigrator;

class SplitSectionWysiwygCommand extends AbstractMigrateCommand
{
    /**
     * Migrate Split Section textarea content to WYSIWYG field.
     *
     * Copies the existing 'text' field content to 'wysiwyg_text' and enables
     * the 'use_wysiwyg' toggle. Skips posts that already have wysiwyg_text content.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database.
     *
     * [--post-id=<id>]
     * : Migrate a single mod-section-split module.
     *
     * [--network]
     * : Run on every site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate split-section-wysiwyg --dry-run
     *     wp eslov migrate split-section-wysiwyg
     *     wp eslov migrate split-section-wysiwyg --post-id=12345
     *     wp eslov migrate split-section-wysiwyg --network
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
        $result = (new SplitSectionWysiwygMigrator($this->dryRun, $this->postId))->migrate();
        $this->logResult($result);
    }
}
