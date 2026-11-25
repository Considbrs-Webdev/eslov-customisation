# Lediga jobb fatal: null ethics policy

Eslöv is on Municipio 7.67.21, the same version that crashes Alingsås. Every job single dies in `Municipio\Controller\SingularJobPosting::setEthicsPolicy()` because it calls `wpautop(null)` when the job has no ethics policy. `WpService` requires a string.

This is a Municipio bug, not missing job data. Alingsås jobs come from Visma Recruit, and that feed has no ethics-policy field. The mapper never writes `ethicsPolicy`. Municipio still calls `wpautop` on the empty value.

Fixed upstream in Municipio **7.76.23** ([helsingborg-stad/Municipio#2363](https://github.com/helsingborg-stad/Municipio/pull/2363)). `setEthicsPolicy()` skips `wpautop` when the value is empty. Still fixed in 7.92.6. Until Eslöv is on 7.76.23 or newer, shim it in `eslov-customisation`. Do not edit the theme.

## Shim

`source/php/Customisations/JobPostingEthicsPolicyShim.php`

On a singular job, wrap the theme `WpService` so `wpautop(null)` becomes `wpautop('')`. Municipio does not expose the `Template` instance, so the wrapper is taken from the `template_include` callback. Only attach it on the job post type. Alingsås uses `lediga-jobb`; confirm Eslöv's slug before copying that check.

```php
<?php

namespace EslovCustomisation\Customisations;

use Municipio\Template;
use WpService\Implementations\WpServiceDecorator;
use WpService\WpService;

/**
 * Lets job singles render when the job has no ethics policy.
 *
 * @upstream-shim id=municipio-jobposting-null-ethics-policy
 * @upstream-repo municipio (library/Controller/SingularJobPosting.php setEthicsPolicy)
 * @upstream-broken setEthicsPolicy passes null to wpautop when hiringOrganization has no ethicsPolicy, and WpService rejects null.
 * @upstream-fix-needed Pass an empty string to wpautop when ethicsPolicy is missing.
 * @remove-when Municipio 7.76.23 or newer is installed. setEthicsPolicy skips wpautop when ethicsPolicy is empty (helsingborg-stad/Municipio#2363). Installed 7.67.21 still calls wpautop(null).
 * @verify-removal Open a job single whose schema has no ethicsPolicy; the page renders.
 */
class JobPostingEthicsPolicyShim
{
    public function __construct()
    {
        add_action('template_redirect', [$this, 'allowNullEthicsPolicy'], 1);
    }

    public function allowNullEthicsPolicy(): void
    {
        if (!is_singular('lediga-jobb')) {
            return;
        }

        $template = $this->findTemplate();
        if (!$template instanceof Template) {
            return;
        }

        $property = (new \ReflectionClass($template))->getProperty('wpService');
        $current = $property->getValue($template);
        if (!$current instanceof WpService || $current instanceof JobPostingNullableWpautop) {
            return;
        }

        $property->setValue($template, new JobPostingNullableWpautop($current));
    }

    private function findTemplate(): ?Template
    {
        global $wp_filter;

        if (!isset($wp_filter['template_include'])) {
            return null;
        }

        foreach ($wp_filter['template_include']->callbacks as $callbacks) {
            foreach ($callbacks as $callback) {
                $function = $callback['function'] ?? null;
                if (is_array($function) && ($function[0] ?? null) instanceof Template) {
                    return $function[0];
                }
            }
        }

        return null;
    }
}

class JobPostingNullableWpautop extends WpServiceDecorator
{
    public function wpautop(?string $text, bool $br = true): string
    {
        return parent::wpautop($text ?? '', $br);
    }
}
```

Register it in `App::registerInstances()`:

```php
Customisations\JobPostingEthicsPolicyShim::class,
```

Composer PSR-4 loads `EslovCustomisation\Customisations\JobPostingEthicsPolicyShim` from that path. The decorator class stays in the same file and is not registered.

## Check

Open a job single with `?debug`. The fatal is gone when the title and body render. After upgrading Municipio to 7.76.23 or newer, delete the class and the `App.php` line, then load a job that has no ethics policy.
