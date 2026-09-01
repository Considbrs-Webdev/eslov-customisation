<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\DesignTokenExporter;
use EslovCustomisation\Migration\DesignTokensMigrator;
use EslovCustomisation\Migration\MigrationResult;

class DesignTokensCommand extends AbstractMigrateCommand
{
    protected const SUPPORTS_FORCE_FLAG = true;

    /**
     * Patch design-builder tokens from legacy Kirki settings and optional patch JSON.
     *
     * `--export` writes `theme_mod('tokens')` to per-blog JSON baselines and
     * skips the migrator. `--force` and `--patches` are ignored when exporting.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database or export files.
     *
     * [--force]
     * : Overwrite existing token values when they differ from legacy theme mods or patches.
     *
     * [--patches=<path>]
     * : Path to a patch JSON file (default: plugin config/styleguide-token-patches.json).
     *
     * [--export]
     * : Write theme_mod('tokens') to per-blog JSON files instead of running the migrator.
     *
     * [--export-dir=<path>]
     * : Directory for exported JSON files (default: plugin config/design-tokens/).
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
     *     wp eslov migrate design-tokens --export
     *     wp eslov migrate design-tokens --export --network
     *     wp eslov migrate design-tokens --export --dry-run --network
     *
     * @param array<int, string> $args
     * @param array<string, mixed> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $this->parseMigrateFlags($assocArgs);
        $this->prepareNetworkMigration($assocArgs);
        $this->logDryRunNotice();

        if ($this->isExportFlag($assocArgs)) {
            $this->warnIgnoredExportFlags($assocArgs);
        } elseif (\WP_CLI\Utils\get_flag_value($assocArgs, 'export-dir', null)) {
            \WP_CLI::warning('--export-dir ignored: set --export to write baseline JSON files.');
        }

        $this->executeAcrossSites($assocArgs, function () use ($assocArgs): void {
            $this->runTask($assocArgs);
        });
    }

    /**
     * Export per-blog JSON baselines, or run DesignTokensMigrator.
     *
     * @param array<string, mixed> $assocArgs
     */
    public function runTask(array $assocArgs): void
    {
        if ($this->isExportFlag($assocArgs)) {
            $exportDir = DesignTokenExporter::resolveDirectory(
                $this->stringFlag($assocArgs, 'export-dir'),
            );
            $result = (new DesignTokenExporter($this->dryRun, $exportDir))->export();
            $this->logExportResult($result);

            return;
        }

        $force = \WP_CLI\Utils\get_flag_value($assocArgs, 'force', false);
        $patchPath = $this->stringFlag($assocArgs, 'patches');

        $result = (new DesignTokensMigrator($this->dryRun, $force, $patchPath))->migrate();
        $this->logResult($result);
    }

    /**
     * @param array<string, mixed> $assocArgs
     */
    private function isExportFlag(array $assocArgs): bool
    {
        return (bool) \WP_CLI\Utils\get_flag_value($assocArgs, 'export', false);
    }

    /**
     * @param array<string, mixed> $assocArgs
     */
    private function stringFlag(array $assocArgs, string $key): ?string
    {
        $value = \WP_CLI\Utils\get_flag_value($assocArgs, $key, null);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $assocArgs
     */
    private function warnIgnoredExportFlags(array $assocArgs): void
    {
        if (\WP_CLI\Utils\get_flag_value($assocArgs, 'force', false)) {
            \WP_CLI::warning('--force ignored when --export is set.');
        }

        if ($this->stringFlag($assocArgs, 'patches') !== null) {
            \WP_CLI::warning('--patches ignored when --export is set.');
        }
    }

    private function logExportResult(MigrationResult $result): void
    {
        foreach ($result->messages as $message) {
            \WP_CLI::log($message);
        }

        $summary = sprintf(
            'Exported %d, skipped %d, errors %d',
            $result->migrated,
            $result->skipped,
            $result->errors
        );

        if ($result->errors > 0) {
            \WP_CLI::warning($summary);
        } else {
            \WP_CLI::success($summary);
        }
    }
}
