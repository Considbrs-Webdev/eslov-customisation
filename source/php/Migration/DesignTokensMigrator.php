<?php

namespace EslovCustomisation\Migration;

class DesignTokensMigrator
{
    public function __construct(
        private bool $dryRun = false,
        private bool $force = false,
    ) {}

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();
        $tokens = $this->loadTokens();
        $changes = [];

        $buttonWeight = $this->extractTypographyVariant('typography_button');
        if ($buttonWeight !== null) {
            $this->applyChange(
                $tokens,
                ['component', '__general__', 'button', '--c-button--font-weight-medium'],
                $buttonWeight,
                sprintf('Set button font-weight to %s (typography_button)', $buttonWeight),
                $changes,
            );
        }

        $boldWeight = $this->extractTypographyVariant('typography_bold');
        if ($boldWeight !== null) {
            $this->applyChange(
                $tokens,
                ['token', '--font-weight-bold'],
                $boldWeight,
                sprintf('Set --font-weight-bold to %s (typography_bold)', $boldWeight),
                $changes,
            );
        }

        if (get_theme_mod('search_form_shape') === '100') {
            $this->applyChange(
                $tokens,
                ['token', '--c-search-form-border-radius'],
                '100px',
                'Set search form border-radius to 100px (search_form_shape pill)',
                $changes,
            );
        }

        if ($changes === []) {
            $result->skipped = 1;
            $result->addMessage('No design token changes needed.');

            return $result;
        }

        foreach ($changes as $change) {
            $result->addMessage($change);
        }

        if (!$this->dryRun) {
            set_theme_mod('tokens', wp_json_encode($tokens));
        }

        $result->migrated = count($changes);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadTokens(): array
    {
        $stored = get_theme_mod('tokens');
        $default = [
            'token' => [],
            'component' => [],
        ];

        if (!is_string($stored)) {
            return $default;
        }

        $decoded = json_decode($stored, true);
        if (!is_array($decoded)) {
            return $default;
        }

        if (!isset($decoded['token']) || !is_array($decoded['token'])) {
            $decoded['token'] = [];
        }

        if (!isset($decoded['component']) || !is_array($decoded['component'])) {
            $decoded['component'] = [];
        }

        return $decoded;
    }

    private function extractTypographyVariant(string $themeModKey): ?string
    {
        $value = get_theme_mod($themeModKey);
        if (!is_array($value)) {
            return null;
        }

        $variant = $value['variant'] ?? null;
        if (!is_string($variant) || $variant === '' || $variant === 'regular') {
            return null;
        }

        return $variant;
    }

    /**
     * @param array<string, mixed> $tokens
     * @param string[] $path
     * @param string[] $changes
     */
    private function applyChange(
        array &$tokens,
        array $path,
        string $value,
        string $description,
        array &$changes,
    ): void {
        $current = $this->getNestedValue($tokens, $path);

        if ($current === $value) {
            return;
        }

        if ($current !== null && !$this->force) {
            return;
        }

        $this->setNestedValue($tokens, $path, $value);

        $message = $description;
        if ($current !== null) {
            $message .= sprintf(' (was %s)', $current);
        }

        $changes[] = $message;
    }

    /**
     * @param array<string, mixed> $array
     * @param string[] $path
     */
    private function getNestedValue(array $array, array $path): ?string
    {
        $current = $array;

        foreach ($path as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }

            $current = $current[$key];
        }

        return is_string($current) || is_int($current) || is_float($current)
            ? (string) $current
            : null;
    }

    /**
     * @param array<string, mixed> $array
     * @param string[] $path
     */
    private function setNestedValue(array &$array, array $path, string $value): void
    {
        $current = &$array;

        foreach ($path as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }

            $current = &$current[$key];
        }

        $current = $value;
    }
}
