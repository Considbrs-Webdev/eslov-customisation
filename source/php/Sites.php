<?php

namespace EslovCustomisation;

/**
 * Multisite blog IDs keyed by hostname label (no TLD).
 *
 * Labels stay stable across .test / .se / .w8e.se.
 */
class Sites
{
    /**
     * @var array<string, int>
     */
    public const IDS = [
        'eslov' => 1,
        'medborgarhuset' => 3,
        'foretag' => 4,
        'programforoffentligmiljo' => 7,
        'varumarkesmanual' => 8,
        'storatorg' => 10,
        'sommarieslov' => 11,
        'historia' => 12,
        'eslovsfesten' => 13,
        'valarbetare' => 14,
        'utveckla' => 15,
        'plus' => 16,
    ];

    /**
     * Whether the current blog matches a hostname label.
     */
    public static function currentIs(string $slug): bool
    {
        $id = self::IDS[$slug] ?? null;

        return $id !== null && get_current_blog_id() === $id;
    }

    /**
     * Whether the current blog is a subsite (not the main Eslöv site).
     */
    public static function isSubsite(): bool
    {
        return !self::currentIs('eslov');
    }
}
