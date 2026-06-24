<?php

namespace EslovCustomisation\Cli;

use EslovCustomisation\Cli\Migrate\DesignTokensCommand;
use EslovCustomisation\Cli\Migrate\ModPostsMixedDisplayCommand;
use EslovCustomisation\Cli\Migrate\ModPostsTaxonomyDisplayCommand;
use EslovCustomisation\Cli\Migrate\ModularityUpgradeCommand;
use EslovCustomisation\Cli\Migrate\ThemeModsCommand;
use EslovCustomisation\Cli\Migrate\WidgetsCommand;

class MigrationCommandRunner
{
    /** @var array<string, class-string<AbstractMigrateCommand>> */
    private const COMMAND_MAP = [
        'eslov migrate modularity-upgrade' => ModularityUpgradeCommand::class,
        'eslov migrate widgets' => WidgetsCommand::class,
        'eslov migrate mod-posts-taxonomy-display' => ModPostsTaxonomyDisplayCommand::class,
        'eslov migrate mod-posts-mixed-display' => ModPostsMixedDisplayCommand::class,
        'eslov migrate theme-mods' => ThemeModsCommand::class,
        'eslov migrate design-tokens' => DesignTokensCommand::class,
    ];

    public static function run(string $command, AbstractMigrateCommand $parent, array $assocArgs): void
    {
        $class = self::COMMAND_MAP[$command] ?? null;

        if ($class === null) {
            \WP_CLI::warning(sprintf('No in-process runner for "%s".', $command));

            return;
        }

        /** @var AbstractMigrateCommand $instance */
        $instance = new $class();
        $parent->copyMigrateStateTo($instance);
        $instance->runTask($assocArgs);
    }

    /**
     * @return class-string<AbstractMigrateCommand>[]
     */
    public static function runnableCommandClasses(): array
    {
        return array_values(self::COMMAND_MAP);
    }
}
