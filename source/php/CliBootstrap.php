<?php

namespace EslovCustomisation;

class CliBootstrap
{
    public static function register(): void
    {
        \WP_CLI::add_command('eslov migrate status', Cli\Migrate\StatusCommand::class);
        \WP_CLI::add_command('eslov migrate widgets', Cli\Migrate\WidgetsCommand::class);
        // \WP_CLI::add_command('eslov migrate meta-keys', Cli\Migrate\MetaKeysCommand::class);
    }
}
