<?php

declare(strict_types=1);

namespace EslovCustomisation\Customisations;

/**
 * Serve search on the friendly `/sok/` endpoint instead of `/?s=term`.
 *
 * A dedicated path can be cached by the page cache, which a query-string
 * search on the front page cannot. Legacy `/?s=` requests are redirected with
 * a temporary 302 (switch to 301 once verified). Remember to flush rewrite rules after deploying.
 */
class Search
{
    private const PATH = 'sok';

    /** Server-side page cache lifetime for the search page, in seconds. */
    private const CACHE_SECONDS = 1200;

    public function __construct()
    {
        add_filter('get_search_form', [$this, 'filterSearchForm']);
        add_action('init', [$this, 'addRewriteRules']);
        add_action('template_redirect', [$this, 'maybeRedirectSearchUrl']);
        add_action('template_redirect', [$this, 'limitCacheLifetime'], 20);
        add_filter('search_link', [$this, 'filterSearchLink'], 10, 2);
        add_filter('Municipio/Template/viewData', [$this, 'showHeaderSearch']);
    }

    /** Keep the configured subpage header search visible on search pages too. */
    public function showHeaderSearch(array $data): array
    {
        if (!is_search()) {
            return $data;
        }

        $locations = $data['customizer']->searchDisplay ?? [];
        $data['showHeaderSearchDesktop'] = in_array('header_sub', $locations, true);
        $data['showHeaderSearchMobile'] = in_array('header_mobile_sub', $locations, true);

        return $data;
    }

    public function filterSearchForm(string $form): string
    {
        $action = esc_url(home_url('/' . self::PATH . '/'));

        return (string) preg_replace('/action=("|\').*?\1/i', 'action="' . $action . '"', $form, 1);
    }

    /**
     * Point WordPress' search permalink (used for canonical/og:url/JSON-LD) at /sok/.
     */
    public function filterSearchLink(string $link, string $query): string
    {
        $base = home_url('/' . self::PATH . '/');

        return $query === '' ? $base : $base . '?s=' . rawurlencode($query);
    }

    public function addRewriteRules(): void
    {
        add_rewrite_rule('^' . self::PATH . '/?$', 'index.php?s=', 'top');
    }

    public function maybeRedirectSearchUrl(): void
    {
        if (!is_search() || is_admin() || wp_doing_ajax()) {
            return;
        }

        $requestPath = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');

        if (str_ends_with($requestPath, self::PATH)) {
            return;
        }

        $target = home_url('/' . self::PATH . '/') . '?s=' . rawurlencode((string) get_query_var('s'));

        foreach (['post_type', 'paged'] as $key) {
            if (isset($_GET[$key])) {
                $target .= '&' . rawurlencode($key) . '=' . rawurlencode((string) $_GET[$key]);
            }
        }

        wp_safe_redirect($target, 302);
        exit;
    }

    /**
     * Shorten the nginx FastCGI cache lifetime for the search page.
     *
     * The page is identical for every term, so a short lifetime costs little
     * and limits how long a stale Typesense state can be served. Skipped if a
     * cache header was already set (e.g. no-store when Typesense is down).
     */
    public function limitCacheLifetime(): void
    {
        if (!is_search() || is_admin() || headers_sent()) {
            return;
        }

        foreach (headers_list() as $header) {
            $isAccel   = stripos($header, 'X-Accel-Expires:') === 0;
            $isNoStore = stripos($header, 'Cache-Control:') === 0 && stripos($header, 'no-store') !== false;

            if ($isAccel || $isNoStore) {
                return;
            }
        }

        header('X-Accel-Expires: ' . self::CACHE_SECONDS);
    }
}
