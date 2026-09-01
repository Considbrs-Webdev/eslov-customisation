<?php

namespace EslovCustomisation\Migration;

/**
 * Installs LTS Montserrat through WordPress' native font library so Municipio
 * Design Builder tokens and wp_print_font_faces() agree.
 */
class NativeFontLibraryMigrator
{
    /**
     * @var array<int, string>
     */
    private const TYPOGRAPHY_MODS = [
        'typography_base',
        'typography_heading',
        'typography_bold',
        'typography_italic',
        'typography_lead',
        'typography_body',
        'typography_button',
        'typography_caption',
        'typography_meta',
        'typography_h1',
        'typography_h2',
        'typography_h3',
        'typography_h4',
        'typography_h5',
        'typography_h6',
        'header_brand_font_settings',
    ];

    /**
     * @var array<int, string>
     */
    private const FONT_FAMILY_TOKEN_KEYS = [
        '--font-family-base',
        '--font-family-heading',
    ];

    /**
     * @var array<int, string>
     */
    private const ACTIVATED_VARIANTS = [
        '400',
        '500',
        '600',
        '700',
        '400italic',
        '500italic',
        '600italic',
        '700italic',
    ];

    public function __construct(
        private readonly bool $dryRun = false,
        private readonly bool $force = false,
    ) {
    }

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();

        if (!$this->nativeFontLibraryIsAvailable()) {
            $result->errors++;
            $result->addMessage('Native font library post types are not available.');

            return $result;
        }

        $this->rewriteTypographyMods($result);
        $this->rewriteDesignTokens($result);

        if ($this->siteUsesMontserrat()) {
            $this->installAndActivateMontserrat($result);
        } else {
            $result->skipped++;
            $result->addMessage('Skip Google Montserrat install: this site does not use a Montserrat family.');
        }

        $this->attachGlobalStylesToTheme($result);
        $this->rewriteFontSourcesToCurrentSite($result);

        if ($result->migrated === 0 && $result->skipped === 0 && $result->errors === 0) {
            $result->skipped = 1;
            $result->addMessage('No font library changes needed.');
        }

        return $result;
    }

    private function rewriteTypographyMods(MigrationResult $result): void
    {
        foreach (self::TYPOGRAPHY_MODS as $modKey) {
            $value = get_theme_mod($modKey, []);

            if (!is_array($value) || !isset($value['font-family']) || !is_string($value['font-family'])) {
                continue;
            }

            $mapped = LegacyUploadedFontFamilyMapper::toKirkiFontFamily($value['font-family']);

            if ($mapped === null) {
                continue;
            }

            $result->addMessage(sprintf(
                '%s %s.font-family %s → %s',
                $this->dryRun ? 'Would set' : 'Set',
                $modKey,
                $value['font-family'],
                $mapped,
            ));

            if (!$this->dryRun) {
                $value['font-family'] = $mapped;
                set_theme_mod($modKey, $value);
            }

            $result->migrated++;
        }
    }

    private function rewriteDesignTokens(MigrationResult $result): void
    {
        $tokens = $this->loadTokens();
        $changed = false;
        $sources = [
            '--font-family-base' => 'typography_base',
            '--font-family-heading' => 'typography_heading',
        ];

        foreach ($sources as $tokenKey => $modKey) {
            $current = $tokens['token'][$tokenKey] ?? null;

            if (is_string($current) && str_starts_with($current, 'var(')) {
                continue;
            }

            $candidate = is_string($current) && $current !== ''
                ? $current
                : $this->typographyFontFamily($modKey);

            if ($candidate === null) {
                continue;
            }

            $mapped = LegacyUploadedFontFamilyMapper::toCssFontFamily($candidate);

            if ($mapped === null || $mapped === $current) {
                continue;
            }

            $tokens['token'][$tokenKey] = $mapped;
            $changed = true;

            $result->addMessage(sprintf(
                '%s token %s %s → %s',
                $this->dryRun ? 'Would set' : 'Set',
                $tokenKey,
                $current ?? 'unset',
                $mapped,
            ));
            $result->migrated++;
        }

        if ($changed && !$this->dryRun) {
            set_theme_mod('tokens', wp_json_encode($tokens) ?: '{"token":{},"component":{}}');
        }
    }

    private function typographyFontFamily(string $modKey): ?string
    {
        $value = get_theme_mod($modKey, []);

        if (!is_array($value) || !isset($value['font-family']) || !is_string($value['font-family'])) {
            return null;
        }

        return $value['font-family'];
    }

    /**
     * @return array{token: array<string, mixed>, component: array<string, mixed>}
     */
    private function loadTokens(): array
    {
        $default = [
            'token' => [],
            'component' => [],
        ];
        $stored = get_theme_mod('tokens');

        if (!is_string($stored)) {
            return $default;
        }

        $decoded = json_decode($stored, true);

        if (!is_array($decoded)) {
            return $default;
        }

        $decoded['token'] = isset($decoded['token']) && is_array($decoded['token']) ? $decoded['token'] : [];
        $decoded['component'] = isset($decoded['component']) && is_array($decoded['component']) ? $decoded['component'] : [];

        return $decoded;
    }

    private function siteUsesMontserrat(): bool
    {
        foreach (self::TYPOGRAPHY_MODS as $modKey) {
            $value = get_theme_mod($modKey, []);

            if (!is_array($value) || !isset($value['font-family']) || !is_string($value['font-family'])) {
                continue;
            }

            if (LegacyUploadedFontFamilyMapper::mapsToMontserrat($value['font-family'])) {
                return true;
            }
        }

        $tokens = $this->loadTokens();

        foreach (self::FONT_FAMILY_TOKEN_KEYS as $tokenKey) {
            $current = $tokens['token'][$tokenKey] ?? null;

            if (is_string($current) && LegacyUploadedFontFamilyMapper::mapsToMontserrat($current)) {
                return true;
            }
        }

        return false;
    }

    private function installAndActivateMontserrat(MigrationResult $result): void
    {
        $definition = $this->getGoogleMontserratDefinition();

        if ($definition === null) {
            $result->errors++;
            $result->addMessage('Google Fonts collection does not include Montserrat.');

            return;
        }

        $familyPostId = $this->findFontFamilyPostId(LegacyUploadedFontFamilyMapper::GOOGLE_FAMILY);
        $missingFaces = $this->missingActivatedFaces($familyPostId, $definition);

        if ($missingFaces === [] && $familyPostId !== null) {
            $result->skipped++;
            $result->addMessage('Skip Montserrat download: all required local font faces already exist.');
        } elseif ($this->dryRun) {
            $result->addMessage(sprintf(
                'Would install %d Montserrat font face(s) from the WordPress Google Fonts collection.',
                count($missingFaces),
            ));
            $result->migrated++;
        } else {
            $installed = $this->withPublicContentUrls(
                fn (): ?int => $this->installFontFamily($definition),
            );

            if ($installed === null) {
                $result->errors++;
                $result->addMessage('Failed to install Montserrat into the native font library.');

                return;
            }

            $familyPostId = $installed;
            $result->addMessage(sprintf('Installed Montserrat native font family (post %d).', $familyPostId));
            $result->migrated++;
        }

        if ($familyPostId === null && $this->dryRun) {
            $result->addMessage('Would create user Global Styles and activate Montserrat.');
            $result->migrated++;

            return;
        }

        if ($familyPostId === null) {
            return;
        }

        $this->activateFontInGlobalStyles($familyPostId, $definition, $result);
    }

    /**
     * @param array{name: string, fontFamily: string, fontFace: array<int, array<string, mixed>>} $definition
     */
    private function installFontFamily(array $definition): ?int
    {
        $this->ensureFileIncludes();

        $familyPostId = $this->createFontFamilyPost($definition['name'], $definition['fontFamily']);

        if ($familyPostId === null) {
            return null;
        }

        $installedCount = 0;

        foreach ($this->filterActivatedFaces($definition['fontFace']) as $fontFace) {
            $installed = $this->installFontFace($familyPostId, $definition['name'], $fontFace);

            if ($installed) {
                $installedCount++;
            }
        }

        if ($installedCount === 0 && !$this->fontFamilyHasLocalFaces($familyPostId)) {
            return null;
        }

        return $familyPostId;
    }

    /**
     * @param array<int, array<string, mixed>> $fontFaces
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterActivatedFaces(array $fontFaces): array
    {
        $allowed = array_fill_keys(self::ACTIVATED_VARIANTS, true);

        return array_values(array_filter(
            $fontFaces,
            function (array $fontFace) use ($allowed): bool {
                $weight = isset($fontFace['fontWeight']) ? trim((string) $fontFace['fontWeight']) : '400';

                if (preg_match('/^\d+\s+\d+$/', $weight) === 1) {
                    return true;
                }

                return isset($allowed[$this->fontFaceVariantKey($fontFace)]);
            },
        ));
    }

    /**
     * @param array<string, mixed> $fontFace
     */
    private function installFontFace(int $familyPostId, string $fontFamily, array $fontFace): bool
    {
        $sources = $this->normalizeSources($fontFace['src'] ?? []);

        if ($sources === []) {
            return false;
        }

        if ($this->fontFaceExistsForVariant($familyPostId, $fontFace)) {
            return true;
        }

        $installedSources = [];
        $fontFile = null;

        foreach ($sources as $source) {
            $installed = $this->sideloadFontSource($source);

            if ($installed === null) {
                continue;
            }

            $installedSources[] = $installed['url'];
            $fontFile ??= $installed['fontFile'];
        }

        if ($installedSources === []) {
            return false;
        }

        $settings = [
            'fontFamily' => sprintf('"%s", sans-serif', $fontFamily),
            'fontStyle' => isset($fontFace['fontStyle']) ? (string) $fontFace['fontStyle'] : 'normal',
            'fontWeight' => isset($fontFace['fontWeight']) ? (string) $fontFace['fontWeight'] : '400',
            'fontDisplay' => 'swap',
            'src' => $installedSources,
        ];

        if (isset($fontFace['unicodeRange']) && is_string($fontFace['unicodeRange']) && $fontFace['unicodeRange'] !== '') {
            $settings['unicodeRange'] = $fontFace['unicodeRange'];
        }

        $postId = wp_insert_post([
            'post_type' => 'wp_font_face',
            'post_parent' => $familyPostId,
            'post_status' => 'publish',
            'post_title' => $this->fontFaceSlug($settings, $fontFamily),
            'post_name' => $this->fontFaceSlug($settings, $fontFamily),
            'post_content' => wp_slash(wp_json_encode($settings) ?: '{}'),
        ], true);

        if (is_wp_error($postId) || !is_int($postId) || $postId <= 0) {
            return false;
        }

        if (is_string($fontFile) && $fontFile !== '') {
            add_post_meta($postId, '_wp_font_face_file', $fontFile);
        }

        return true;
    }

    /**
     * @return array{url: string, fontFile: string}|null
     */
    private function sideloadFontSource(string $source): ?array
    {
        if ($source === '') {
            return null;
        }

        $this->ensureFileIncludes();

        $temporaryFile = download_url($source);

        if (is_wp_error($temporaryFile) || !is_string($temporaryFile) || $temporaryFile === '') {
            return null;
        }

        $path = (string) parse_url($source, PHP_URL_PATH);
        $fileName = basename($path);
        $fileName = $fileName !== '' && $fileName !== '/' ? $fileName : 'font.woff2';

        $file = [
            'name' => $fileName,
            'tmp_name' => $temporaryFile,
            'error' => 0,
            'size' => (int) filesize($temporaryFile),
        ];

        $overrides = [
            'test_form' => false,
        ];

        if (class_exists(\WP_Font_Utils::class) && method_exists(\WP_Font_Utils::class, 'get_allowed_font_mime_types')) {
            $overrides['mimes'] = \WP_Font_Utils::get_allowed_font_mime_types();
        }

        add_filter('upload_dir', '_wp_filter_font_directory');
        $sideloaded = wp_handle_sideload($file, $overrides);
        remove_filter('upload_dir', '_wp_filter_font_directory');

        if (!is_array($sideloaded) || empty($sideloaded['file']) || empty($sideloaded['url'])) {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }

            return null;
        }

        return [
            'url' => $this->rewriteFontUrlToCurrentSite((string) $sideloaded['url']),
            'fontFile' => $this->relativeFontsPath((string) $sideloaded['file']),
        ];
    }

    /**
     * @param array{name: string, fontFamily: string, fontFace: array<int, array<string, mixed>>} $definition
     */
    private function activateFontInGlobalStyles(int $familyPostId, array $definition, MigrationResult $result): void
    {
        $postId = $this->getOrCreateGlobalStylesPostId();

        if ($postId === null) {
            if ($this->dryRun) {
                $result->addMessage('Would create user Global Styles and activate Montserrat.');
                $result->migrated++;

                return;
            }

            $result->errors++;
            $result->addMessage('Could not load or create the user Global Styles post.');

            return;
        }

        $post = get_post($postId);
        $data = $this->decodeGlobalStyles($post instanceof \WP_Post ? (string) $post->post_content : '');
        $custom = $data['settings']['typography']['fontFamilies']['custom'] ?? [];
        $custom = is_array($custom) ? array_values(array_filter($custom, 'is_array')) : [];

        $activated = $this->activatedFontFamilyPayload($familyPostId, $definition);

        if ($activated === null) {
            $result->errors++;
            $result->addMessage('Montserrat has no local font faces to activate.');

            return;
        }

        $replaced = false;

        foreach ($custom as $index => $existing) {
            if (($existing['slug'] ?? '') !== $activated['slug']) {
                continue;
            }

            if ($this->force || !$this->familyHasLocalSrc($existing)) {
                $custom[$index] = $activated;
                $replaced = true;
            }

            break;
        }

        if (!$this->familyListHasSlug($custom, $activated['slug'])) {
            $custom[] = $activated;
            $replaced = true;
        }

        $filtered = array_values(array_filter(
            $custom,
            fn (array $family): bool => $this->familyHasLocalSrc($family),
        ));

        if (!$this->familyListHasSlug($filtered, $activated['slug'])) {
            $filtered[] = $activated;
            $replaced = true;
        }

        $original = $data['settings']['typography']['fontFamilies']['custom'] ?? null;
        $needsUserFlag = $this->globalStylesNeedsUserFlag($post instanceof \WP_Post ? (string) $post->post_content : '');

        if (
            !$replaced
            && !$this->force
            && !$needsUserFlag
            && wp_json_encode($filtered) === wp_json_encode($original)
        ) {
            $result->skipped++;
            $result->addMessage('Skip Global Styles font activation: Montserrat already active.');

            return;
        }

        $data['settings']['typography']['fontFamilies']['custom'] = $filtered;

        $result->addMessage(sprintf(
            '%s Montserrat in Global Styles post %d.',
            $this->dryRun ? 'Would activate' : 'Activated',
            $postId,
        ));

        if (!$this->dryRun) {
            wp_update_post([
                'ID' => $postId,
                'post_content' => wp_slash(wp_json_encode($data) ?: '{}'),
            ]);
            $this->clearThemeJsonCache();
        }

        $result->migrated++;
    }

    /**
     * @param array{name: string, fontFamily: string, fontFace: array<int, array<string, mixed>>} $definition
     *
     * @return array{name: string, slug: string, fontFamily: string, fontFace: array<int, array<string, mixed>>}|null
     */
    private function activatedFontFamilyPayload(int $familyPostId, array $definition): ?array
    {
        $faces = [];

        foreach (get_posts([
            'post_type' => 'wp_font_face',
            'post_status' => 'publish',
            'post_parent' => $familyPostId,
            'posts_per_page' => -1,
        ]) as $face) {
            $settings = json_decode((string) $face->post_content, true);

            if (!is_array($settings) || !$this->familyHasLocalSrc(['fontFace' => [$settings]])) {
                continue;
            }

            $settings['src'] = array_map(
                fn (mixed $src): string => is_string($src) ? $this->rewriteFontUrlToCurrentSite($src) : '',
                $this->normalizeSources($settings['src'] ?? []),
            );
            $settings['src'] = array_values(array_filter($settings['src']));
            $faces[] = $settings;
        }

        if ($faces === []) {
            return null;
        }

        return [
            'name' => $definition['name'],
            'slug' => sanitize_title($definition['name']),
            'fontFamily' => $definition['fontFamily'],
            'fontFace' => $faces,
        ];
    }

    private function attachGlobalStylesToTheme(MigrationResult $result): void
    {
        if (!$this->siteUsesMontserrat() && !$this->force) {
            $result->skipped++;
            $result->addMessage('Skip wp_theme assignment: this site does not use Montserrat.');

            return;
        }
        $postId = $this->getOrCreateGlobalStylesPostId();

        if ($postId === null) {
            if ($this->dryRun) {
                $result->addMessage(sprintf(
                    'Would create Global Styles post and assign wp_theme=%s.',
                    get_stylesheet(),
                ));
                $result->migrated++;

                return;
            }

            $result->errors++;
            $result->addMessage('Could not attach Global Styles: post missing.');

            return;
        }

        $stylesheet = get_stylesheet();
        $terms = wp_get_object_terms($postId, 'wp_theme', ['fields' => 'names']);
        $hasTerm = is_array($terms) && in_array($stylesheet, $terms, true);

        if ($hasTerm && !$this->force) {
            $result->skipped++;
            $result->addMessage(sprintf(
                'Skip wp_theme term: Global Styles post %d already assigned to %s.',
                $postId,
                $stylesheet,
            ));

            return;
        }

        $result->addMessage(sprintf(
            '%s wp_theme=%s on Global Styles post %d.',
            $this->dryRun ? 'Would assign' : 'Assigned',
            $stylesheet,
            $postId,
        ));

        if (!$this->dryRun) {
            wp_set_object_terms($postId, $stylesheet, 'wp_theme');
            $this->clearThemeJsonCache();
        }

        $result->migrated++;
    }

    private function getOrCreateGlobalStylesPostId(): ?int
    {
        $stylesheet = get_stylesheet();
        $fromTheme = $this->findGlobalStylesPostIdByTheme($stylesheet);

        if ($fromTheme !== null) {
            return $fromTheme;
        }

        $fromPath = $this->findGlobalStylesPostIdByPath($stylesheet);

        if ($fromPath !== null) {
            if (!$this->dryRun) {
                wp_set_object_terms($fromPath, $stylesheet, 'wp_theme');
            }

            return $fromPath;
        }

        if ($this->dryRun) {
            return null;
        }

        $postId = wp_insert_post([
            'post_content' => wp_json_encode([
                'version' => class_exists(\WP_Theme_JSON::class) ? \WP_Theme_JSON::LATEST_SCHEMA : 3,
                'isGlobalStylesUserThemeJSON' => true,
            ]) ?: '{}',
            'post_status' => 'publish',
            'post_title' => 'Custom Styles',
            'post_type' => 'wp_global_styles',
            'post_name' => sprintf('wp-global-styles-%s', rawurlencode($stylesheet)),
        ], true);

        if (is_wp_error($postId) || !is_int($postId) || $postId <= 0) {
            return null;
        }

        wp_set_object_terms($postId, $stylesheet, 'wp_theme');

        return $postId;
    }

    private function findGlobalStylesPostIdByTheme(string $stylesheet): ?int
    {
        $posts = get_posts([
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_type' => 'wp_global_styles',
            'post_status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => 'wp_theme',
                    'field' => 'name',
                    'terms' => $stylesheet,
                ],
            ],
        ]);

        return $posts !== [] ? (int) $posts[0]->ID : null;
    }

    private function findGlobalStylesPostIdByPath(string $stylesheet): ?int
    {
        $existing = get_page_by_path(
            sprintf('wp-global-styles-%s', rawurlencode($stylesheet)),
            OBJECT,
            'wp_global_styles',
        );

        return $existing instanceof \WP_Post ? (int) $existing->ID : null;
    }

    private function globalStylesNeedsUserFlag(string $postContent): bool
    {
        $decoded = json_decode($postContent, true);

        return !is_array($decoded) || empty($decoded['isGlobalStylesUserThemeJSON']);
    }

    /**
     * @return array{name: string, fontFamily: string, fontFace: array<int, array<string, mixed>>}|null
     */
    private function getGoogleMontserratDefinition(): ?array
    {
        if (!class_exists(\WP_Font_Library::class) || !method_exists(\WP_Font_Library::class, 'get_instance')) {
            return null;
        }

        $library = \WP_Font_Library::get_instance();

        if (!is_object($library) || !method_exists($library, 'get_font_collections')) {
            return null;
        }

        foreach ((array) $library->get_font_collections() as $collection) {
            if (!is_object($collection) || !method_exists($collection, 'get_data')) {
                continue;
            }

            $data = $collection->get_data();

            if (is_wp_error($data) || !is_array($data)) {
                continue;
            }

            foreach ($data['font_families'] ?? [] as $fontDefinition) {
                if (!is_array($fontDefinition)) {
                    continue;
                }

                $settings = $fontDefinition['font_family_settings'] ?? null;

                if (!is_array($settings)) {
                    continue;
                }

                $name = isset($settings['name']) ? trim((string) $settings['name']) : '';
                $css = isset($settings['fontFamily']) ? trim((string) $settings['fontFamily']) : '';
                $faces = isset($settings['fontFace']) && is_array($settings['fontFace'])
                    ? array_values(array_filter($settings['fontFace'], 'is_array'))
                    : [];

                if ($name === '' || $css === '' || $faces === []) {
                    continue;
                }

                if (!LegacyUploadedFontFamilyMapper::mapsToMontserrat($name) && !LegacyUploadedFontFamilyMapper::mapsToMontserrat($css)) {
                    continue;
                }

                return [
                    'name' => $name,
                    'fontFamily' => $css,
                    'fontFace' => $faces,
                ];
            }
        }

        return null;
    }

    private function createFontFamilyPost(string $name, string $cssFontFamily): ?int
    {
        $existing = $this->findFontFamilyPostId($name);

        if ($existing !== null) {
            return $existing;
        }

        $slug = sanitize_title($name);
        $postId = wp_insert_post([
            'post_type' => 'wp_font_family',
            'post_status' => 'publish',
            'post_title' => $name,
            'post_name' => $slug,
            'post_content' => wp_slash(wp_json_encode([
                'name' => $name,
                'slug' => $slug,
                'fontFamily' => $cssFontFamily,
            ]) ?: '{}'),
        ], true);

        if (is_wp_error($postId) || !is_int($postId) || $postId <= 0) {
            return null;
        }

        return $postId;
    }

    private function findFontFamilyPostId(string $name): ?int
    {
        $slug = sanitize_title($name);
        $existing = get_page_by_path($slug, OBJECT, 'wp_font_family');

        if ($existing instanceof \WP_Post) {
            return (int) $existing->ID;
        }

        return null;
    }

    /**
     * @param array{name: string, fontFamily: string, fontFace: array<int, array<string, mixed>>} $definition
     *
     * @return array<int, array<string, mixed>>
     */
    private function missingActivatedFaces(?int $familyPostId, array $definition): array
    {
        $needed = [];

        foreach ($this->filterActivatedFaces($definition['fontFace']) as $fontFace) {
            $needed[$this->fontFaceVariantKey($fontFace)] = $fontFace;
        }

        if ($familyPostId === null) {
            return array_values($needed);
        }

        $existing = $this->existingLocalVariantKeys($familyPostId);

        return array_values(array_filter(
            $needed,
            static fn (string $variantKey): bool => !isset($existing[$variantKey]),
            ARRAY_FILTER_USE_KEY,
        ));
    }

    /**
     * @return array<string, true>
     */
    private function existingLocalVariantKeys(int $familyPostId): array
    {
        $keys = [];

        foreach (get_posts([
            'post_type' => 'wp_font_face',
            'post_status' => 'publish',
            'post_parent' => $familyPostId,
            'posts_per_page' => -1,
        ]) as $face) {
            $settings = json_decode((string) $face->post_content, true);

            if (!is_array($settings) || !$this->familyHasLocalSrc(['fontFace' => [$settings]])) {
                continue;
            }

            $keys[$this->fontFaceVariantKey($settings)] = true;
        }

        return $keys;
    }

    /**
     * @param array<string, mixed> $fontFace
     */
    private function fontFaceExistsForVariant(int $familyPostId, array $fontFace): bool
    {
        return isset($this->existingLocalVariantKeys($familyPostId)[$this->fontFaceVariantKey($fontFace)]);
    }

    private function fontFamilyHasLocalFaces(int $familyPostId): bool
    {
        return $this->existingLocalVariantKeys($familyPostId) !== [];
    }

    /**
     * @param array<string, mixed> $family
     */
    private function familyHasLocalSrc(array $family): bool
    {
        $faces = $family['fontFace'] ?? [];

        if (!is_array($faces)) {
            return false;
        }

        foreach ($faces as $face) {
            if (!is_array($face)) {
                continue;
            }

            foreach ($this->normalizeSources($face['src'] ?? []) as $src) {
                $path = $this->urlToLocalPath($src);

                if (is_string($path) && $path !== '' && is_readable($path)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $families
     */
    private function familyListHasSlug(array $families, string $slug): bool
    {
        foreach ($families as $family) {
            if (($family['slug'] ?? '') === $slug) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function fontFaceSlug(array $settings, string $fontFamily): string
    {
        if (class_exists(\WP_Font_Utils::class) && method_exists(\WP_Font_Utils::class, 'get_font_face_slug')) {
            $slug = \WP_Font_Utils::get_font_face_slug($settings);

            if (is_string($slug) && $slug !== '') {
                return $slug;
            }
        }

        return sanitize_title(sprintf(
            '%s-%s-%s',
            $fontFamily,
            $settings['fontStyle'] ?? 'normal',
            $settings['fontWeight'] ?? '400',
        ));
    }

    /**
     * @param array<string, mixed> $fontFace
     */
    private function fontFaceVariantKey(array $fontFace): string
    {
        $weight = isset($fontFace['fontWeight']) ? strtolower(trim((string) $fontFace['fontWeight'])) : '400';
        $style = isset($fontFace['fontStyle']) ? strtolower(trim((string) $fontFace['fontStyle'])) : 'normal';

        $weight = match ($weight) {
            'normal', 'regular' => '400',
            'bold' => '700',
            default => preg_match('/^\d+$/', $weight) === 1 ? $weight : '400',
        };

        return $style === 'italic' ? $weight . 'italic' : $weight;
    }

    /**
     * @param string|array<int, mixed> $sources
     *
     * @return array<int, string>
     */
    private function normalizeSources(string|array $sources): array
    {
        $sources = is_array($sources) ? $sources : [$sources];

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $source): string => is_string($source) ? trim($source) : '',
            $sources,
        ))));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeGlobalStyles(string $postContent): array
    {
        $decoded = json_decode($postContent, true);

        if (!is_array($decoded)) {
            $decoded = [];
        }

        $decoded['version'] = isset($decoded['version']) && is_int($decoded['version'])
            ? $decoded['version']
            : (class_exists(\WP_Theme_JSON::class) ? \WP_Theme_JSON::LATEST_SCHEMA : 3);
        $decoded['isGlobalStylesUserThemeJSON'] = true;
        $decoded['settings'] = isset($decoded['settings']) && is_array($decoded['settings']) ? $decoded['settings'] : [];

        return $decoded;
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function withPublicContentUrls(callable $callback): mixed
    {
        $rewrite = fn (string $url): string => $this->publicContentUrl($url);

        $contentUrlFilter = static function (string $url) use ($rewrite): string {
            return $rewrite($url);
        };
        $uploadDirFilter = static function (array $dirs) use ($rewrite): array {
            foreach (['url', 'baseurl'] as $key) {
                if (isset($dirs[$key]) && is_string($dirs[$key])) {
                    $dirs[$key] = $rewrite($dirs[$key]);
                }
            }

            return $dirs;
        };

        add_filter('content_url', $contentUrlFilter);
        add_filter('upload_dir', $uploadDirFilter, 20);

        try {
            return $callback();
        } finally {
            remove_filter('content_url', $contentUrlFilter);
            remove_filter('upload_dir', $uploadDirFilter, 20);
        }
    }

    private function publicContentUrl(string $url): string
    {
        return str_replace('/wp/wp-content/', '/wp-content/', $url);
    }

    private function rewriteFontUrlToCurrentSite(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        $normalized = $this->publicContentUrl($url);
        $path = (string) parse_url($normalized, PHP_URL_PATH);

        if ($path === '' || !str_contains($path, '/wp-content/')) {
            return $normalized;
        }

        if (!$this->fontUrlNeedsRewrite($normalized)) {
            return $normalized;
        }

        $relative = ltrim(
            substr($path, (int) strpos($path, '/wp-content/') + strlen('/wp-content/')),
            '/',
        );

        return $this->publicContentUrl(content_url($relative));
    }

    private function fontUrlNeedsRewrite(string $url): bool
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        if ($path === '' || !str_contains($path, '/wp-content/uploads/')) {
            return false;
        }

        $currentHost = (string) parse_url(home_url(), PHP_URL_HOST);
        $sourceHost = (string) parse_url($url, PHP_URL_HOST);

        return $sourceHost !== '' && $sourceHost !== $currentHost;
    }

    private function rewriteFontSourcesToCurrentSite(MigrationResult $result): void
    {
        $this->rewriteFontFacePostSources($result);
        $this->rewriteGlobalStylesFontSources($result);
    }

    private function rewriteFontFacePostSources(MigrationResult $result): void
    {
        foreach (get_posts([
            'post_type' => 'wp_font_face',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]) as $face) {
            if (!$face instanceof \WP_Post) {
                continue;
            }

            $settings = json_decode((string) $face->post_content, true);

            if (!is_array($settings)) {
                continue;
            }

            $sources = $this->normalizeSources($settings['src'] ?? []);
            $rewritten = array_map(
                fn (string $source): string => $this->rewriteFontUrlToCurrentSite($source),
                $sources,
            );

            if ($rewritten === $sources) {
                continue;
            }

            $settings['src'] = $rewritten;

            $result->addMessage(sprintf(
                '%s rewrite wp_font_face %d src to current site host.',
                $this->dryRun ? 'Would' : 'Did',
                (int) $face->ID,
            ));

            if (!$this->dryRun) {
                wp_update_post([
                    'ID' => (int) $face->ID,
                    'post_content' => wp_slash(wp_json_encode($settings) ?: '{}'),
                ]);
            }

            $result->migrated++;
        }
    }

    private function rewriteGlobalStylesFontSources(MigrationResult $result): void
    {
        $stylesheet = get_stylesheet();
        $postId = $this->findGlobalStylesPostIdByTheme($stylesheet)
            ?? $this->findGlobalStylesPostIdByPath($stylesheet);

        if ($postId === null) {
            return;
        }

        $post = get_post($postId);

        if (!$post instanceof \WP_Post) {
            return;
        }

        $data = $this->decodeGlobalStyles((string) $post->post_content);
        $custom = $data['settings']['typography']['fontFamilies']['custom'] ?? [];
        $custom = is_array($custom) ? array_values(array_filter($custom, 'is_array')) : [];
        $changed = false;

        foreach ($custom as $familyIndex => $family) {
            $faces = $family['fontFace'] ?? [];

            if (!is_array($faces)) {
                continue;
            }

            foreach ($faces as $faceIndex => $face) {
                if (!is_array($face)) {
                    continue;
                }

                $sources = $this->normalizeSources($face['src'] ?? []);
                $rewritten = array_map(
                    fn (string $source): string => $this->rewriteFontUrlToCurrentSite($source),
                    $sources,
                );

                if ($rewritten === $sources) {
                    continue;
                }

                $custom[$familyIndex]['fontFace'][$faceIndex]['src'] = $rewritten;
                $changed = true;
            }
        }

        if (!$changed) {
            return;
        }

        $data['settings']['typography']['fontFamilies']['custom'] = $custom;

        $result->addMessage(sprintf(
            '%s rewrite Global Styles post %d font src to current site host.',
            $this->dryRun ? 'Would' : 'Did',
            $postId,
        ));

        if (!$this->dryRun) {
            wp_update_post([
                'ID' => $postId,
                'post_content' => wp_slash(wp_json_encode($data) ?: '{}'),
            ]);
            $this->clearThemeJsonCache();
        }

        $result->migrated++;
    }

    private function urlToLocalPath(string $url): ?string
    {
        $path = (string) parse_url($this->publicContentUrl($url), PHP_URL_PATH);

        if ($path === '' || !str_contains($path, '/wp-content/')) {
            return null;
        }

        $relative = substr($path, (int) strpos($path, '/wp-content/'));
        $candidate = dirname(WP_CONTENT_DIR) . $relative;

        return $candidate !== '' ? $candidate : null;
    }

    private function relativeFontsPath(string $path): string
    {
        $fontDir = wp_get_font_dir();
        $baseDir = isset($fontDir['basedir']) && is_string($fontDir['basedir']) ? rtrim($fontDir['basedir'], '/') : '';

        if ($baseDir !== '' && str_starts_with($path, $baseDir)) {
            return ltrim(substr($path, strlen($baseDir)), '/');
        }

        return basename($path);
    }

    private function nativeFontLibraryIsAvailable(): bool
    {
        return post_type_exists('wp_font_family') && post_type_exists('wp_font_face');
    }

    private function ensureFileIncludes(): void
    {
        if (!function_exists('download_url') && defined('ABSPATH')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
    }

    private function clearThemeJsonCache(): void
    {
        if (class_exists(\WP_Theme_JSON_Resolver::class) && method_exists(\WP_Theme_JSON_Resolver::class, 'clean_cached_data')) {
            \WP_Theme_JSON_Resolver::clean_cached_data();
        }

        wp_cache_delete('wp_get_global_stylesheet', 'theme_json');
        wp_cache_delete('wp_get_global_settings', 'theme_json');
    }
}
