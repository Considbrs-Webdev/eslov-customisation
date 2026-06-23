<?php

namespace EslovCustomisation\Migration;

class DesignTokenPatchLoader
{
    public function __construct(
        private readonly string $patchFilePath,
    ) {}

    public function apply(DesignTokenState $state): void
    {
        if (!is_readable($this->patchFilePath)) {
            return;
        }

        $contents = file_get_contents($this->patchFilePath);
        if ($contents === false) {
            return;
        }

        $patch = json_decode($contents, true);
        if (!is_array($patch)) {
            return;
        }

        if (isset($patch['token']) && is_array($patch['token'])) {
            $state->mergePatch(['token' => $patch['token']]);
        }

        if (isset($patch['component']) && is_array($patch['component'])) {
            $state->mergePatch(['component' => $patch['component']]);
        }
    }
}
