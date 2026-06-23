<?php

namespace EslovCustomisation\Migration\ThemeModCorrections;

use EslovCustomisation\Migration\ThemeModCorrectionInterface;

class VerticalMenuIndentSublevelsCorrection implements ThemeModCorrectionInterface
{
    public const KEY = 'vetical_menu_indent_sublevels';

    public function key(): string
    {
        return self::KEY;
    }

    public function desiredValue(): bool
    {
        return true;
    }

    public function description(): string
    {
        return 'Enable vertical menu “Indent each level” (adds c-nav--indent-sublevels to drawer/sidebar nav).';
    }

    public function isApplied(mixed $current): bool
    {
        return filter_var($current, FILTER_VALIDATE_BOOLEAN);
    }
}
