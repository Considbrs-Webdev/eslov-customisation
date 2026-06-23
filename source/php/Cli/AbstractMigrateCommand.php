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

    /**
     * @param array<string, mixed> $assocArgs
     */
    protected function isNetworkFlag(array $assocArgs): bool
    {
        return (bool) \WP_CLI\Utils\get_flag_value($assocArgs, 'network', false);
    }

    /**
     * @param array<string, mixed> $assocArgs
     */
    protected function prepareNetworkMigration(array $assocArgs): void
    {
        if (!$this->isNetworkFlag($assocArgs)) {
            return;
        }

        if (!is_multisite()) {
            \WP_CLI::warning('--network ignored: not a multisite installation.');

            return;
        }

        self::ensurePluginNetworkActive();
    }

    /**
     * @param array<string, mixed> $assocArgs
     * @param callable(): void $runner
     */
    protected function executeAcrossSites(array $assocArgs, callable $runner): void
    {
        if (!$this->isNetworkFlag($assocArgs) || !is_multisite()) {
            $runner();

            return;
        }

        $sites = get_sites([
            'number' => 0,
            'archived' => 0,
            'deleted' => 0,
            'spam' => 0,
        ]);

        \WP_CLI::log(sprintf('Running on %d network site(s)...', count($sites)));

        foreach ($sites as $site) {
            $blogId = (int) $site->blog_id;
            switch_to_blog($blogId);

            \WP_CLI::log('');
            \WP_CLI::log(sprintf('--- Site %d: %s ---', $blogId, get_site_url()));

            $runner();

            restore_current_blog();
        }
    }

    public static function ensurePluginNetworkActive(): void
    {
        if (!is_multisite()) {
            return;
        }

        $plugin = 'eslov-customisation/eslov-customisation.php';

        if (is_plugin_active_for_network($plugin)) {
            return;
        }

        $result = activate_plugin($plugin, '', true);

        if (is_wp_error($result)) {
            \WP_CLI::warning(sprintf(
                'Could not network-activate eslov-customisation: %s',
                $result->get_error_message(),
            ));

            return;
        }

        \WP_CLI::log('Network-activated eslov-customisation.');
    }

    protected function copyMigrateStateTo(AbstractMigrateCommand $target): void
    {
        $target->dryRun = $this->dryRun;
        $target->postId = $this->postId;
    }

    /**
     * @param array<string, mixed> $assocArgs
     */
    abstract public function runTask(array $assocArgs): void;

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

    /**
     * @param array<string, mixed> $assocArgs
     */
    protected function networkOptionDocblock(): string
    {
        return <<<'DOC'

     * [--network]
     * : Run on every site in the network (uses switch_to_blog; network-activates this plugin if needed).
     *
DOC;
    }
}
