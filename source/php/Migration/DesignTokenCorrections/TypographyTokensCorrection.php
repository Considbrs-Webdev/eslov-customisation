<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

class TypographyTokensCorrection implements DesignTokenCorrectionInterface
{
    /**
     * Legacy Kirki typography group → design-builder token paths.
     * Only maps properties the new type-scale system still exposes as global tokens.
     *
     * @var array<int, array{
     *     mod: string,
     *     field: string,
     *     path: string[],
     *     default?: string,
     *     resolve?: 'variant'
     * }>
     */
    private const MAPPINGS = [
        [
            'mod' => 'typography_base',
            'field' => 'variant',
            'path' => ['token', '--font-weight-normal'],
            'default' => 'regular',
            'resolve' => 'variant',
        ],
        [
            'mod' => 'typography_heading',
            'field' => 'variant',
            'path' => ['token', '--font-weight-heading'],
            'default' => '700',
            'resolve' => 'variant',
        ],
        [
            'mod' => 'typography_bold',
            'field' => 'variant',
            'path' => ['token', '--font-weight-bold'],
            'default' => '700',
            'resolve' => 'variant',
        ],
        [
            'mod' => 'typography_lead',
            'field' => 'variant',
            'path' => ['token', '--font-weight-medium'],
            'default' => '500',
            'resolve' => 'variant',
        ],
        [
            'mod' => 'typography_button',
            'field' => 'variant',
            'path' => ['component', '__general__', 'button', '--c-button--font-weight-medium'],
            'default' => '700',
            'resolve' => 'variant',
        ],
        [
            'mod' => 'typography_base',
            'field' => 'line-height',
            'path' => ['token', '--line-height-base'],
        ],
        [
            'mod' => 'typography_heading',
            'field' => 'line-height',
            'path' => ['token', '--line-height-heading'],
            'default' => '1.33',
        ],
        [
            'mod' => 'typography_base',
            'field' => 'letter-spacing',
            'path' => ['token', '--letter-spacing-base'],
            'default' => '0',
        ],
        [
            'mod' => 'typography_heading',
            'field' => 'letter-spacing',
            'path' => ['token', '--letter-spacing-heading'],
            'default' => '0',
        ],
        [
            'mod' => 'typography_heading',
            'field' => 'font-family',
            'path' => ['token', '--font-family-heading'],
        ],
    ];

  /** @var array<string, string> */
    private const TOKEN_DEFAULTS = [
        '--font-weight-normal' => '400',
        '--font-weight-medium' => '500',
        '--font-weight-bold' => '700',
        '--font-weight-heading' => '700',
        '--line-height-base' => '1.625',
        '--line-height-heading' => '1.33',
        '--letter-spacing-base' => '0',
        '--letter-spacing-heading' => '0',
    ];

    public function apply(DesignTokenState $state): void
    {
        foreach (self::MAPPINGS as $mapping) {
            $value = $this->resolveValue($mapping);
            if ($value === null) {
                continue;
            }

            $tokenKey = end($mapping['path']);
            if (
                !$state->isForce()
                && isset(self::TOKEN_DEFAULTS[$tokenKey])
                && $this->normalizeDecimal($value) === $this->normalizeDecimal(self::TOKEN_DEFAULTS[$tokenKey])
            ) {
                continue;
            }

            $state->applyChange(
                $mapping['path'],
                $value,
                sprintf(
                    'Set %s to %s (%s.%s)',
                    $tokenKey,
                    $value,
                    $mapping['mod'],
                    $mapping['field'],
                ),
            );
        }
    }

    /**
     * @param array{mod: string, field: string, path: string[], default?: string, resolve?: string} $mapping
     */
    private function resolveValue(array $mapping): ?string
    {
        $raw = LegacyThemeModReader::typographyField($mapping['mod'], $mapping['field']);

        if ($raw === null && isset($mapping['default']) && LegacyThemeModReader::hasTypographyMod($mapping['mod'])) {
            $raw = $mapping['default'];
        }

        if ($raw === null) {
            return null;
        }

        if (($mapping['resolve'] ?? null) === 'variant') {
            return LegacyThemeModReader::normalizeVariant($raw);
        }

        return $raw;
    }

    private function normalizeDecimal(string $value): string
    {
        $float = (float) $value;

        return rtrim(rtrim(number_format($float, 3, '.', ''), '0'), '.');
    }
}
