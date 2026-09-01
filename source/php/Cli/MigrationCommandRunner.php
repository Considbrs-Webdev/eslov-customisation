<?php

namespace EslovCustomisation\Cli;

use EslovCustomisation\Cli\Migrate\DesignTokensCommand;
use EslovCustomisation\Cli\Migrate\FontsCommand;
use EslovCustomisation\Cli\Migrate\ManualInputDataRepairCommand;
use EslovCustomisation\Cli\Migrate\ModPostsMixedDisplayCommand;
use EslovCustomisation\Cli\Migrate\ModPostsTaxonomyDisplayCommand;
use EslovCustomisation\Cli\Migrate\ModularityUpgradeCommand;
use EslovCustomisation\Cli\Migrate\MunicipioUpgradeCommand;
use EslovCustomisation\Cli\Migrate\OnePageShowTitleCommand;
use EslovCustomisation\Cli\Migrate\SectionSpacingCommand;
use EslovCustomisation\Cli\Migrate\SectionTextAutopCommand;
use EslovCustomisation\Cli\Migrate\ThemeModsCommand;
use EslovCustomisation\Cli\Migrate\WidgetsCommand;

class MigrationCommandRunner
{
    /** @var array<string, class-string<AbstractMigrateCommand>> */
    private const COMMAND_MAP = [
        'eslov migrate modularity-upgrade' => ModularityUpgradeCommand::class,
        'eslov migrate municipio-upgrade' => MunicipioUpgradeCommand::class,
        'eslov migrate manual-input-data-repair' => ManualInputDataRepairCommand::class,
        'eslov migrate widgets' => WidgetsCommand::class,
        'eslov migrate mod-posts-taxonomy-display' => ModPostsTaxonomyDisplayCommand::class,
        'eslov migrate mod-posts-mixed-display' => ModPostsMixedDisplayCommand::class,
        'eslov migrate theme-mods' => ThemeModsCommand::class,
        'eslov migrate design-tokens' => DesignTokensCommand::class,
        'eslov migrate fonts' => FontsCommand::class,
        'eslov migrate one-page-show-title' => OnePageShowTitleCommand::class,
        'eslov migrate section-spacing' => SectionSpacingCommand::class,
        'eslov migrate section-text-autop' => SectionTextAutopCommand::class,
    ];

    public static function hasRunner(string $command): bool
    {
        return isset(self::COMMAND_MAP[$command]);
    }

    /**
     * Whether the in-process command declares `--force`.
     */
    public static function supportsForceFlag(string $command): bool
    {
        $class = self::COMMAND_MAP[$command] ?? null;

        return $class !== null && $class::supportsForceFlag();
    }

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
