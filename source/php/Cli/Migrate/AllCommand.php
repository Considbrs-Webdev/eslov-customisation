<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Cli\MigrationCommandRunner;
use EslovCustomisation\Migration\MigrationRegistry;

class AllCommand extends AbstractMigrateCommand
{
    /**
     * Run all ready Eslöv migration tasks in order.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database.
     *
     * [--force]
     * : Pass --force to migrations that support it.
     *
     * [--post-id=<id>]
     * : Pass --post-id to migrations that support it.
     *
     * [--network]
     * : Run every ready migration on each site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate all --dry-run
     *     wp eslov migrate all
     *     wp eslov migrate all --force
     *     wp eslov migrate all --network
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
        $tasks = MigrationRegistry::runnable();

        if ($tasks === []) {
            \WP_CLI::warning('No ready migrations to run.');

            return;
        }

        \WP_CLI::log(sprintf('Running %d migration(s)...', count($tasks)));

        foreach ($tasks as $task) {
            \WP_CLI::log('');
            \WP_CLI::log('==> ' . $task['command']);

            if ($this->isNetworkFlag($assocArgs) && is_multisite()) {
                MigrationCommandRunner::run($task['command'], $this, $assocArgs);
            } else {
                $this->runSubcommand($task['command'], $assocArgs);
            }
        }

        \WP_CLI::log('');
        \WP_CLI::success(sprintf('All %d migration(s) finished.', count($tasks)));
    }

    /**
     * @param array<string, mixed> $assocArgs
     */
    private function runSubcommand(string $command, array $assocArgs): void
    {
        $flags = [];

        if ($this->dryRun) {
            $flags[] = '--dry-run';
        }

        if (\WP_CLI\Utils\get_flag_value($assocArgs, 'force', false)) {
            $flags[] = '--force';
        }

        if ($this->postId !== null) {
            $flags[] = '--post-id=' . $this->postId;
        }

        $fullCommand = $command;

        if ($flags !== []) {
            $fullCommand .= ' ' . implode(' ', $flags);
        }

        \WP_CLI::runcommand($fullCommand, ['launch' => false]);
    }
}
