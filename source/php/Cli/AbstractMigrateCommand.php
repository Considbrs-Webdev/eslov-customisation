<?php

namespace EslovCustomisation\Cli;

use EslovCustomisation\Migration\MigrationResult;

abstract class AbstractMigrateCommand extends \WP_CLI_Command
{
    protected bool $dryRun = false;

    protected ?int $postId = null;

    /**
     * @param array<string, mixed> $assocArgs
     */
    protected function parseMigrateFlags(array $assocArgs): void
    {
        $this->dryRun = \WP_CLI\Utils\get_flag_value($assocArgs, 'dry-run', false);
        $postId = \WP_CLI\Utils\get_flag_value($assocArgs, 'post-id', null);

        if ($postId !== null && $postId !== '') {
            $this->postId = (int) $postId;
        }
    }

    protected function logResult(MigrationResult $result): void
    {
        foreach ($result->messages as $message) {
            \WP_CLI::log($message);
        }

        if ($result->errors > 0) {
            \WP_CLI::warning(sprintf(
                'Migrated %d, skipped %d, errors %d',
                $result->migrated,
                $result->skipped,
                $result->errors
            ));
        } else {
            \WP_CLI::success(sprintf(
                'Migrated %d, skipped %d, errors %d',
                $result->migrated,
                $result->skipped,
                $result->errors
            ));
        }
    }

    protected function logDryRunNotice(): void
    {
        if ($this->dryRun) {
            \WP_CLI::log('Dry run — no changes written.');
        }
    }
}
