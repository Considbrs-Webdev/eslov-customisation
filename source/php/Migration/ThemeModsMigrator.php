<?php

namespace EslovCustomisation\Migration;

use EslovCustomisation\Migration\ThemeModCorrections\VerticalMenuIndentSublevelsCorrection;

class ThemeModsMigrator
{
    /** @var ThemeModCorrectionInterface[] */
    private array $corrections;

    public function __construct(
        private readonly bool $dryRun = false,
        private readonly bool $force = false,
    ) {
        $this->corrections = [
            new VerticalMenuIndentSublevelsCorrection(),
        ];
    }

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();

        foreach ($this->corrections as $correction) {
            $key = $correction->key();
            $current = get_theme_mod($key, false);

            if ($correction->isApplied($current) && !$this->force) {
                $result->skipped++;
                $result->addMessage(sprintf('Skip %s: already enabled.', $key));

                continue;
            }

            $result->addMessage(sprintf(
                '%s (%s)',
                $correction->description(),
                $key,
            ));

            if (!$this->dryRun) {
                set_theme_mod($key, $correction->desiredValue());
            }

            $result->migrated++;
        }

        if ($result->migrated === 0 && $result->skipped === 0) {
            $result->skipped = 1;
            $result->addMessage('No theme mod changes needed.');
        }

        return $result;
    }
}
