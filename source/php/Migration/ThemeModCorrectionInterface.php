<?php

namespace EslovCustomisation\Migration;

interface ThemeModCorrectionInterface
{
    public function key(): string;

    public function desiredValue(): mixed;

    public function description(): string;

    public function isApplied(mixed $current): bool;
}
