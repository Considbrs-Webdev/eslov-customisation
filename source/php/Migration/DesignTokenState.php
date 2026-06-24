<?php

namespace EslovCustomisation\Migration;

class DesignTokenState
{
    /** @var array<string, mixed> */
    private array $tokens;

    /** @var string[] */
    private array $changes = [];

    public function __construct(
        private readonly bool $force = false,
    ) {
        $this->tokens = self::loadFromThemeMod();
    }

    /**
     * @return array<string, mixed>
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * @return string[]
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    public function hasChanges(): bool
    {
        return $this->changes !== [];
    }

    public function isForce(): bool
    {
        return $this->force;
    }

    public function toJson(): string
    {
        return wp_json_encode($this->tokens) ?: '{"token":{},"component":{}}';
    }

    /**
     * @param string[] $path
     */
    public function applyChange(array $path, string $value, string $description): void
    {
        $current = $this->getNestedValue($this->tokens, $path);

        if ($current === $value) {
            return;
        }

        if ($current !== null && !$this->force) {
            return;
        }

        $this->setNestedValue($this->tokens, $path, $value);

        $message = $description;
        if ($current !== null) {
            $message .= sprintf(' (was %s)', $current);
        }

        $this->changes[] = $message;
    }

    /**
     * @param string[] $path
     */
    public function getValue(array $path): ?string
    {
        return $this->getNestedValue($this->tokens, $path);
    }

    /**
     * @param string[] $path
     */
    public function removeValue(array $path, string $description): void
    {
        $current = $this->getNestedValue($this->tokens, $path);

        if ($current === null) {
            return;
        }

        $this->unsetNestedValue($this->tokens, $path);

        $this->changes[] = sprintf('%s (was %s)', $description, $current);
    }

    /**
     * @param string[] $path
     * @param string[] $replaceableValues
     */
    public function applyChangeWhenUnsetOrCurrentIn(
        array $path,
        string $value,
        array $replaceableValues,
        string $description,
    ): void {
        $current = $this->getValue($path);

        if ($current === null) {
            $this->applyChange($path, $value, $description);

            return;
        }

        $normalizedCurrent = $this->normalizeDecimal($current);
        $normalizedReplaceable = array_map(
            fn (string $replaceableValue): string => $this->normalizeDecimal($replaceableValue),
            $replaceableValues,
        );

        if (!$this->force && !in_array($normalizedCurrent, $normalizedReplaceable, true)) {
            return;
        }

        if ($normalizedCurrent === $this->normalizeDecimal($value)) {
            return;
        }

        $this->setNestedValue($this->tokens, $path, $value);
        $this->changes[] = sprintf('%s (was %s)', $description, $current);
    }

    /**
     * @param array<string, mixed> $patch
     */
    public function mergePatch(array $patch, string $pathPrefix = ''): void
    {
        foreach ($patch as $key => $value) {
            $segment = $pathPrefix === '' ? (string) $key : $pathPrefix . '.' . $key;

            if (is_array($value)) {
                $this->mergePatch($value, $segment);

                continue;
            }

            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                continue;
            }

            $path = explode('.', $segment);
            $this->applyChange(
                $path,
                (string) $value,
                sprintf('Patch %s → %s', implode('.', $path), (string) $value),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadFromThemeMod(): array
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

    /**
     * @param array<string, mixed> $array
     * @param string[] $path
     */
    private function unsetNestedValue(array &$array, array $path): void
    {
        if ($path === []) {
            return;
        }

        $key = array_pop($path);

        if ($path === []) {
            unset($array[$key]);

            return;
        }

        $current = &$array;

        foreach ($path as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return;
            }

            $current = &$current[$segment];
        }

        if (!is_array($current)) {
            return;
        }

        unset($current[$key]);

        $this->pruneEmptyAncestors($array, $path);
    }

    /**
     * @param array<string, mixed> $array
     * @param string[] $path
     */
    private function pruneEmptyAncestors(array &$array, array $path): void
    {
        if ($path === []) {
            return;
        }

        $key = array_pop($path);

        if ($path === []) {
            if (isset($array[$key]) && is_array($array[$key]) && $array[$key] === []) {
                unset($array[$key]);
            }

            return;
        }

        $current = &$array;

        foreach ($path as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return;
            }

            $current = &$current[$segment];
        }

        if (isset($current[$key]) && is_array($current[$key]) && $current[$key] === []) {
            unset($current[$key]);
            $this->pruneEmptyAncestors($array, $path);
        }
    }

    private function normalizeDecimal(string $value): string
    {
        $float = (float) $value;

        return rtrim(rtrim(number_format($float, 3, '.', ''), '0'), '.');
    }
}
