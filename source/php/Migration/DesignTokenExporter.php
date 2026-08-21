<?php

namespace EslovCustomisation\Migration;

class DesignTokenExporter
{
    private readonly string $exportDir;

    /**
     * @param bool $dryRun When true, log the target path without writing files.
     * @param string $exportDir Absolute export directory; empty uses the plugin default.
     */
    public function __construct(
        private readonly bool $dryRun = false,
        string $exportDir = '',
    ) {
        $this->exportDir = $exportDir !== '' ? $exportDir : self::defaultDirectory();
    }

    /**
     * Default directory for committed per-blog token baselines.
     */
    public static function defaultDirectory(): string
    {
        return rtrim(ESLOV_CUSTOMISATION_PATH . 'config/design-tokens', '/\\');
    }

    /**
     * Resolve `--export-dir` to an absolute path (cwd-relative when needed).
     */
    public static function resolveDirectory(?string $path): string
    {
        if ($path === null || $path === '') {
            return self::defaultDirectory();
        }

        $path = rtrim($path, '/\\');

        if (!path_is_absolute($path)) {
            $cwd = getcwd();
            $path = trailingslashit(is_string($cwd) ? $cwd : '') . $path;
        }

        return $path;
    }

    /**
     * Stable production-ish filename slug for the current blog (without `.json`).
     *
     * Blog 1 is always `main`. Other blogs use the first subdomain label of
     * `get_site_url()` after stripping `.ddev.site` and path.
     */
    public static function fileSlug(): string
    {
        if (get_current_blog_id() === 1) {
            return 'main';
        }

        $parts = wp_parse_url(get_site_url());
        $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';
        $host = preg_replace('/\.ddev\.site$/i', '', $host) ?? $host;
        $label = explode('.', $host)[0] ?? '';
        $slug = self::sanitizeFileSlug($label);

        if ($slug !== '') {
            return $slug;
        }

        $path = is_array($parts) ? trim((string) ($parts['path'] ?? ''), '/') : '';
        $pathSlug = self::sanitizeFileSlug(explode('/', $path)[0] ?? '');

        return $pathSlug !== '' ? $pathSlug : 'blog-' . get_current_blog_id();
    }

    /**
     * Absolute path of the baseline JSON file for the current blog.
     */
    public function filePath(): string
    {
        return $this->exportDir . '/' . self::fileSlug() . '.json';
    }

    /**
     * Write (or log) `theme_mod('tokens')` as pretty-printed baseline JSON.
     */
    public function export(): MigrationResult
    {
        $result = new MigrationResult();
        $state = new DesignTokenState();
        $payload = $state->toExportPayload();
        $path = $this->filePath();
        $tokenKeys = count($payload['token']);
        $componentKeys = count($payload['component']);
        $counts = sprintf('token keys: %d, component keys: %d', $tokenKeys, $componentKeys);

        if ($this->dryRun) {
            $result->migrated = 1;
            $result->addMessage(sprintf('Would export %s (%s)', $path, $counts));

            return $result;
        }

        if (!wp_mkdir_p($this->exportDir)) {
            $result->errors = 1;
            $result->addMessage(sprintf('Could not create directory %s', $this->exportDir));

            return $result;
        }

        $written = file_put_contents($path, $state->toPrettyJson() . "\n");

        if ($written === false) {
            $result->errors = 1;
            $result->addMessage(sprintf('Could not write %s', $path));

            return $result;
        }

        $result->migrated = 1;
        $result->addMessage(sprintf('Exported %s (%s)', $path, $counts));

        return $result;
    }

    private static function sanitizeFileSlug(string $value): string
    {
        $slug = strtolower($value);
        $slug = preg_replace('/[^a-z0-9-]+/', '', $slug) ?? '';

        return $slug;
    }
}
