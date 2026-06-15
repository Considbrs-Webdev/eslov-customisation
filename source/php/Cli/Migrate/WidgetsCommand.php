<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\ClassicWidgetMigrator;

class WidgetsCommand extends AbstractMigrateCommand
{
    /**
     * Convert legacy Modularity classic widgets to block shortcode widgets.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate widgets --dry-run
     *     wp eslov migrate widgets
     *
     * @param array<int, string> $args
     * @param array<string, mixed> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $this->parseMigrateFlags($assocArgs);
        $this->logDryRunNotice();

        $result = (new ClassicWidgetMigrator($this->dryRun))->migrate();
        $this->logResult($result);
    }
}
