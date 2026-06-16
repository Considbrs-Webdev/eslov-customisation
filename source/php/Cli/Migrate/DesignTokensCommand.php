<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\DesignTokensMigrator;

class DesignTokensCommand extends AbstractMigrateCommand
{
    /**
     * Patch Design Builder tokens from legacy Kirki typography and search settings.
     *
     * Maps typography_button → --c-button--font-weight-medium, typography_bold →
     * --font-weight-bold, and search_form_shape pill → --c-search-form-border-radius.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database.
     *
     * [--force]
     * : Overwrite existing token values when they differ from legacy theme mods.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate design-tokens --dry-run
     *     wp eslov migrate design-tokens
     *     wp eslov migrate design-tokens --force
     *
     * @param array<int, string> $args
     * @param array<string, mixed> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $this->parseMigrateFlags($assocArgs);
        $this->logDryRunNotice();

        $force = \WP_CLI\Utils\get_flag_value($assocArgs, 'force', false);
        $result = (new DesignTokensMigrator($this->dryRun, $force))->migrate();
        $this->logResult($result);
    }
}
