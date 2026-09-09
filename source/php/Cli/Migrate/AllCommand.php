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
     * : Pass --force to subcommands that declare it. design-tokens always runs with --force so Eslöv patches overwrite Municipio V41 tokens.
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

            $taskArgs = $this->assocArgsForTask($task['command'], $assocArgs);

            if ($this->isNetworkFlag($assocArgs) && is_multisite()) {
                if (MigrationCommandRunner::hasRunner($task['command'])) {
                    MigrationCommandRunner::run($task['command'], $this, $taskArgs);
                } else {
                    $this->runSubcommand($task['command'], $taskArgs, passNetworkFlag: false);
                }
            } else {
                $this->runSubcommand($task['command'], $taskArgs);
            }
        }

        \WP_CLI::log('');
        \WP_CLI::success(sprintf('All %d migration(s) finished.', count($tasks)));
    }

    /**
     * @param array<string, mixed> $assocArgs
     */
    private function runSubcommand(string $command, array $assocArgs, ?bool $passNetworkFlag = null): void
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

        $shouldPassNetwork = $passNetworkFlag ?? $this->isNetworkFlag($assocArgs);

        if ($shouldPassNetwork && $this->isNetworkFlag($assocArgs)) {
            $flags[] = '--network';
        }

        $fullCommand = $command;

        if ($flags !== []) {
            $fullCommand .= ' ' . implode(' ', $flags);
        }

        \WP_CLI::runcommand($fullCommand, ['launch' => false]);
    }

    /**
     * Restrict `--force` to commands that declare it, and always force design-tokens.
     *
     * Design-token corrections must overwrite Municipio V41 output. Forwarding
     * `--force` to every subcommand breaks commands such as one-page-content.
     *
     * @param array<string, mixed> $assocArgs
     * @return array<string, mixed>
     */
    private function assocArgsForTask(string $command, array $assocArgs): array
    {
        if ($this->shouldPassForce($command, $assocArgs)) {
            $assocArgs['force'] = true;
        } else {
            unset($assocArgs['force']);
        }

        return $assocArgs;
    }

    /**
     * @param array<string, mixed> $assocArgs
     */
    private function shouldPassForce(string $command, array $assocArgs): bool
    {
        if ($command === 'eslov migrate design-tokens') {
            return true;
        }

        if (!\WP_CLI\Utils\get_flag_value($assocArgs, 'force', false)) {
            return false;
        }

        return MigrationCommandRunner::supportsForceFlag($command);
    }
}
