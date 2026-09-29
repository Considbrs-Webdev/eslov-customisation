<?php

namespace EslovCustomisation\Migration;

use Municipio\Helper\AcfService;
use Municipio\Helper\WpService;

/**
 * Runs pending Municipio theme DB upgrades (V41 tokens, V42/V43 fonts, etc.).
 *
 * Upstream {@see \Municipio\Upgrade} only hooks `wp`, which never fires in WP-CLI.
 * Without this, the first browser request overwrites `theme_mod('tokens')` after
 * `eslov migrate design-tokens` has already patched them.
 */
class MunicipioUpgradeRunner
{
    private const VERSION_OPTION = 'municipio_db_version';

    public function __construct(private bool $dryRun = false)
    {
    }

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();

        if (!class_exists(\Municipio\Upgrade::class)) {
            $result->errors++;
            $result->addMessage('Municipio\\Upgrade class not found — is the Municipio theme loaded?');

            return $result;
        }

        $current = $this->currentVersion();
        $target = $this->targetVersion();

        if ($current >= $target) {
            $result->skipped++;
            $result->addMessage(sprintf(
                'Municipio database already at version %d.',
                $current,
            ));

            return $result;
        }

        if ($this->dryRun) {
            $result->skipped++;
            $result->addMessage(sprintf(
                'Would run Municipio upgrade from version %d to %d.',
                $current,
                $target,
            ));

            return $result;
        }

        try {
            (new \Municipio\Upgrade(WpService::get(), AcfService::get()))->initUpgrade();
        } catch (\Exception $exception) {
            $result->errors++;
            $result->addMessage($exception->getMessage());

            return $result;
        }

        $after = $this->currentVersion();

        if ($after > $current) {
            $result->migrated++;
            $result->addMessage(sprintf(
                'Municipio database upgraded from version %d to %d.',
                $current,
                $after,
            ));

            return $result;
        }

        $result->errors++;
        $result->addMessage(sprintf(
            'Municipio upgrade finished but version is still %d (expected %d).',
            $after,
            $target,
        ));

        return $result;
    }

    private function currentVersion(): int
    {
        $stored = get_option(self::VERSION_OPTION);

        return is_numeric($stored) ? (int) $stored : 1;
    }

    private function targetVersion(): int
    {
        return (int) (new \ReflectionProperty(\Municipio\Upgrade::class, 'dbVersion'))->getDefaultValue();
    }
}
