<?php

namespace EslovCustomisation\Customisations;

/** Select larger image variants earlier, targeting approximately 1.5x density. */
class ImageContainerQueries
{
    private const BREAKPOINT_SCALE = 2 / 3;

    public function __construct()
    {
        add_filter(
            'ComponentLibrary/Component/Image/ContainerQueryData',
            [$this, 'adjustBreakpoints'],
        );
    }

    public function adjustBreakpoints(?array $variants): ?array
    {
        if (empty($variants)) {
            return $variants;
        }

        foreach ($variants as &$variant) {
            foreach (['landscape', 'portrait'] as $orientation) {
                if (!isset($variant['media'][$orientation])) {
                    continue;
                }

                // Keep the library's ranges and orientation logic, including the
                // final open-ended range. Only change the pixel thresholds.
                $variant['media'][$orientation] = preg_replace_callback(
                    '/((?:min|max)-(?:width|height):\s*)(\d+(?:\.\d+)?)px/',
                    static fn (array $match): string => $match[1]
                        . round((float) $match[2] * self::BREAKPOINT_SCALE, 3) . 'px',
                    $variant['media'][$orientation],
                );
            }
        }
        unset($variant);

        return $variants;
    }
}
