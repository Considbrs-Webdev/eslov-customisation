<?php

declare(strict_types=1);

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
