<?php

declare(strict_types=1);

namespace EslovCustomisation\Customisations;

use EslovCustomisation\Support\MatomoSettings;

/**
 * Prints the Matomo snippet in wp_head; optionally deferred until Pressidium "analytics" consent.
 */
class MatomoTracking
{
    public function __construct()
    {
        add_action('wp_head', [$this, 'render']);
    }

    public function render(): void
    {
        $url = MatomoSettings::get('url');
        $containerId = MatomoSettings::get('container_id');
        $siteId = MatomoSettings::get('site_id');

        if ($url === '' || ($containerId === '' && $siteId === '')) {
            return;
        }

        $url = trailingslashit($url);
        $gated = MatomoSettings::requireConsent();
        $attrs = $gated ? ' type="text/plain" data-cookiecategory="analytics"' : '';

        if ($containerId !== '') {
            $script = $this->containerScript($url, $containerId);
            $label = 'Matomo Tag Manager';
        } else {
            $script = $this->classicScript($url, $siteId, $gated);
            $label = 'Matomo';
        }

        printf("<!-- %1\$s -->\n<script%2\$s>\n%3\$s</script>\n<!-- End %1\$s -->\n", $label, $attrs, $script); // phpcs:ignore WordPress.Security.EscapeOutput
    }

    private function containerScript(string $url, string $containerId): string
    {
        $src = $this->js($url . 'js/container_' . $containerId . '.js');

        return <<<JS
        var _mtm = window._mtm = window._mtm || [];
        _mtm.push({'mtm.startTime': (new Date().getTime()), 'event': 'mtm.Start'});
        (function() {
          var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
          g.async=true;
          g.src={$src};
          s.parentNode.insertBefore(g,s);
        })();

        JS;
    }

    /**
     * When gated, Pressidium running the script is itself the consent, so Matomo's own
     * requireCookieConsent queue (which nothing ever releases) is left out.
     */
    private function classicScript(string $url, string $siteId, bool $gated): string
    {
        $u = $this->js($url);
        $id = $this->js($siteId);
        $requireConsent = $gated ? '' : "_paq.push(['requireCookieConsent']);\n        ";
        $consentSync = $gated ? '' : $this->consentSyncScript();

        return <<<JS
        var _paq = window._paq = window._paq || [];
        {$requireConsent}_paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function() {
          var u={$u};
          _paq.push(['setTrackerUrl', u+'matomo.php']);
          _paq.push(['setSiteId', {$id}]);
          var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
          g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
        })();
        {$consentSync}
        JS;
    }

    /**
     * Ungated Matomo runs cookieless (requireCookieConsent); once the visitor accepts "analytics"
     * in Pressidium, switch to cookie tracking, and back again if the choice is withdrawn.
     */
    private function consentSyncScript(): string
    {
        return <<<JS
        (function() {
          function sync(e) {
            var c = e && e.detail && e.detail.cookie;
            if (!c || !c.categories) { return; }
            _paq.push([c.categories.indexOf('analytics') !== -1 ? 'rememberCookieConsentGiven' : 'forgetCookieConsentGiven']);
          }
          window.addEventListener('pressidium-cookie-consent-accepted', sync);
          window.addEventListener('pressidium-cookie-consent-changed', sync);
        })();

        JS;
    }

    private function js(string $value): string
    {
        return (string) wp_json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
    }
}
