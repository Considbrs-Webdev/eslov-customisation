<?php

namespace EslovCustomisation\Migration;

class ModularityUpgradeRunner
{
    private const TARGET_VERSION = 8;

    private const VERSION_OPTION = 'modularity_db_version';

    public function __construct(private bool $dryRun = false)
    {
    }

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();

        if (!class_exists(\Modularity\Upgrade::class)) {
            $result->errors++;
            $result->addMessage('Modularity\\Upgrade class not found — is the Municipio theme loaded?');

            return $result;
        }

        $current = (int) get_option(self::VERSION_OPTION, 0);

        if ($current >= self::TARGET_VERSION) {
            $result->skipped++;
            $result->addMessage(sprintf(
                'Modularity database already at version %d.',
                $current,
            ));

            return $result;
        }

        if ($this->dryRun) {
            $result->skipped++;
            $result->addMessage(sprintf(
                'Would run modularity upgrade from version %d to %d.',
                $current,
                self::TARGET_VERSION,
            ));

            return $result;
        }

        (new \Modularity\Upgrade())->upgrade();

        $after = (int) get_option(self::VERSION_OPTION, 0);

        if ($after >= self::TARGET_VERSION) {
            $result->migrated++;
            $result->addMessage(sprintf(
                'Modularity database upgraded to version %d.',
                $after,
            ));

            return $result;
        }

        $result->errors++;
        $result->addMessage(sprintf(
            'Modularity upgrade finished but version is still %d (expected %d).',
            $after,
            self::TARGET_VERSION,
        ));

        return $result;
    }
}
