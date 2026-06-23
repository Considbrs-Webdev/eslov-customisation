<?php

namespace EslovCustomisation;

class CliBootstrap
{
    public static function register(): void
    {
        \WP_CLI::add_command('eslov migrate status', Cli\Migrate\StatusCommand::class);
        \WP_CLI::add_command('eslov migrate all', Cli\Migrate\AllCommand::class);
        \WP_CLI::add_command('eslov migrate widgets', Cli\Migrate\WidgetsCommand::class);
        \WP_CLI::add_command(
            'eslov migrate mod-posts-taxonomy-display',
            Cli\Migrate\ModPostsTaxonomyDisplayCommand::class
        );
        \WP_CLI::add_command(
            'eslov migrate mod-posts-mixed-display',
            Cli\Migrate\ModPostsMixedDisplayCommand::class
        );
        \WP_CLI::add_command(
            'eslov migrate design-tokens',
            Cli\Migrate\DesignTokensCommand::class
        );
        \WP_CLI::add_command(
            'eslov migrate theme-mods',
            Cli\Migrate\ThemeModsCommand::class
        );
        // \WP_CLI::add_command('eslov migrate meta-keys', Cli\Migrate\MetaKeysCommand::class);
    }
}
