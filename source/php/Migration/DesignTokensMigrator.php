<?php

namespace EslovCustomisation\Migration;

use EslovCustomisation\Migration\DesignTokenCorrections\ButtonDefaultSurfaceCorrection;
use EslovCustomisation\Migration\DesignTokenCorrections\FontSizeScaleRatioCorrection;
use EslovCustomisation\Migration\DesignTokenCorrections\TypographyTokensCorrection;
use EslovCustomisation\Migration\DesignTokenCorrections\FooterLinkContrastCorrection;
use EslovCustomisation\Migration\DesignTokenCorrections\PrimaryPaletteCorrection;
use EslovCustomisation\Migration\DesignTokenCorrections\FieldBorderRadiusCorrection;
use EslovCustomisation\Migration\DesignTokenCorrections\SearchFormShapeCorrection;

class DesignTokensMigrator
{
    /** @var DesignTokenCorrectionInterface[] */
    private array $corrections;

    public function __construct(
        private bool $dryRun = false,
        private bool $force = false,
        private ?string $patchFilePath = null,
    ) {
        $this->corrections = [
            new TypographyTokensCorrection(),
            new FontSizeScaleRatioCorrection(),
            new SearchFormShapeCorrection(),
            new FieldBorderRadiusCorrection(),
            new PrimaryPaletteCorrection(),
            new FooterLinkContrastCorrection(),
            new ButtonDefaultSurfaceCorrection(),
        ];
    }

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();
        $state = new DesignTokenState($this->force);

        foreach ($this->corrections as $correction) {
            $correction->apply($state);
        }

        $patchPath = $this->patchFilePath ?? $this->defaultPatchFilePath();
        (new DesignTokenPatchLoader($patchPath))->apply($state);

        if (!$state->hasChanges()) {
            $result->skipped = 1;
            $result->addMessage('No design token changes needed.');

            return $result;
        }

        foreach ($state->getChanges() as $change) {
            $result->addMessage($change);
        }

        if (!$this->dryRun) {
            set_theme_mod('tokens', $state->toJson());
        }

        $result->migrated = count($state->getChanges());

        return $result;
    }

    private function defaultPatchFilePath(): string
    {
        return ESLOV_CUSTOMISATION_PATH . 'config/styleguide-token-patches.json';
    }
}
