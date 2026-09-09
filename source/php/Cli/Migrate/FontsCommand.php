<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Cli\AbstractMigrateCommand;
use EslovCustomisation\Migration\NativeFontLibraryMigrator;

class FontsCommand extends AbstractMigrateCommand
{
    protected const SUPPORTS_FORCE_FLAG = true;

    /**
     * Install LTS Montserrat as a native WordPress / Municipio font.
     *
     * Maps uploaded Kirki font slugs to Google Montserrat, downloads faces into
     * the native font library, points Design Builder tokens at the expected
     * CSS family, and attaches user Global Styles to the active theme so
     * wp_print_font_faces() emits @font-face.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Log planned changes without writing to the database or downloading files.
     *
     * [--force]
     * : Re-apply token and Global Styles updates even when already migrated.
     *
     * [--network]
     * : Run on every site in the network.
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate fonts --dry-run
     *     wp eslov migrate fonts
     *     wp eslov migrate fonts --network
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
        $force = \WP_CLI\Utils\get_flag_value($assocArgs, 'force', false);

        $result = (new NativeFontLibraryMigrator($this->dryRun, $force))->migrate();
        $this->logResult($result);
    }
}
