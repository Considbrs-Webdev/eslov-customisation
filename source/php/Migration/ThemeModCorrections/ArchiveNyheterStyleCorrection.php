<?php

namespace EslovCustomisation\Migration\ThemeModCorrections;

use EslovCustomisation\Migration\ThemeModCorrectionInterface;

/**
 * LTS Municipio "collection" on nyheter was horizontal rows with small thumbnails.
 * Standard Municipio collection is full-width vertical image cards.
 *
 * Only nyheter — driftsinformation intentionally keeps collection (desired appearance).
 * Run via `wp eslov migrate theme-mods --network` on multisite.
 */
class ArchiveNyheterStyleCorrection implements ThemeModCorrectionInterface
{
    public const KEY = 'archive_nyheter_style';

    private const LEGACY_STYLE = 'collection';

    private const TARGET_STYLE = 'newsitem';

    public function key(): string
    {
        return self::KEY;
    }

    public function desiredValue(): string
    {
        return self::TARGET_STYLE;
    }

    public function description(): string
    {
        return sprintf(
            'Remap nyheter archive style from LTS %s to %s (horizontal news rows).',
            self::LEGACY_STYLE,
            self::TARGET_STYLE,
        );
    }

    public function isApplied(mixed $current): bool
    {
        return $current !== self::LEGACY_STYLE;
    }
}
