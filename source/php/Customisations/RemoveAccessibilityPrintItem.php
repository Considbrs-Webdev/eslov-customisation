<?php

declare(strict_types=1);

namespace EslovCustomisation\Customisations;

/**
 * Remove the "Skriv ut" (Print) item from Municipio's accessibility menu.
 *
 * Municipio adds it via AppendPrintMenuItem (no toggle). We strip it from the
 * final menu through the Municipio/Accessibility/Items filter. Priority 20
 * runs after PdfGenerator::replacePrintWithPdf so it is removed either way.
 */
class RemoveAccessibilityPrintItem
{
    public function __construct()
    {
        add_filter('Municipio/Accessibility/Items', [$this, 'removePrint'], 20);
    }

    /**
     * @param array<string, mixed> $items
     * @return array<string, mixed>
     */
    public function removePrint(array $items): array
    {
        unset($items['print']);

        return $items;
    }
}
