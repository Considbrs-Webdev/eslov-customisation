<?php

namespace EslovCustomisation\Migration;

class MigrationResult
{
    public int $migrated = 0;

    public int $skipped = 0;

    public int $errors = 0;

    /** @var string[] */
    public array $messages = [];

    public function addMessage(string $message): void
    {
        $this->messages[] = $message;
    }
}
