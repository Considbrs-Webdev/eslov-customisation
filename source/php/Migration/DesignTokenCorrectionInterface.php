<?php

namespace EslovCustomisation\Migration;

interface DesignTokenCorrectionInterface
{
    public function apply(DesignTokenState $state): void;
}
