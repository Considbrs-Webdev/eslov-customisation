<?php

declare(strict_types=1);

namespace EslovCustomisation\Migration;

class ManualInputDataRepairMigrator
{
    private const LEGACY_SOURCE_META = 'posts_data_source';

    private const LEGACY_SOURCE_VALUE = 'input';

    private const LEGACY_REPEATER_COUNT = 'data';

    private const TARGET_REPEATER = 'manual_inputs';

    /** @var string[] */
    private const ALLOWED_STATUSES = ['publish', 'draft', 'private'];

    public function __construct(
        private bool $dryRun = false,
        private ?int $postId = null,
    ) {}

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();
        $moduleIds = $this->postId !== null ? [$this->postId] : $this->findCandidateModuleIds();

        foreach ($moduleIds as $moduleId) {
            $this->migrateModule($moduleId, $result);
        }

        return $result;
    }

    /**
     * @return int[]
     */
    private function findCandidateModuleIds(): array
    {
        global $wpdb;

        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} m
                ON m.post_id = p.ID
                AND m.meta_key = %s
                AND m.meta_value = %s
            WHERE p.post_type = %s
            AND p.post_status IN ('publish', 'draft', 'private')
            ORDER BY p.ID ASC",
            self::LEGACY_SOURCE_META,
            self::LEGACY_SOURCE_VALUE,
            'mod-manualinput',
        ));

        return array_map('intval', $rows ?: []);
    }

    private function migrateModule(int $moduleId, MigrationResult $result): void
    {
        if (get_post_type($moduleId) !== 'mod-manualinput') {
            $result->skipped++;
            $result->addMessage("Skipped {$moduleId}: not mod-manualinput");

            return;
        }

        if (!in_array(get_post_status($moduleId), self::ALLOWED_STATUSES, true)) {
            $result->skipped++;
            $result->addMessage("Skipped {$moduleId}: status not eligible");

            return;
        }

        if (get_post_meta($moduleId, self::LEGACY_SOURCE_META, true) !== self::LEGACY_SOURCE_VALUE) {
            $result->skipped++;
            $result->addMessage("Skipped {$moduleId}: not former posts_data_source=input");

            return;
        }

        $dataRows = $this->readDataRows($moduleId);
        $manualInputRows = $this->readManualInputRows($moduleId);

        if (!$this->hasLegacyDataContent($dataRows)) {
            $result->skipped++;
            $result->addMessage("Skipped {$moduleId}: empty legacy data source");

            return;
        }

        $analysis = $this->analyzeGaps($dataRows, $manualInputRows);

        if (!$analysis['hasRowLoss'] && !$analysis['hasFieldLoss']) {
            $result->skipped++;
            $result->addMessage("Skipped {$moduleId}: manual_inputs already complete vs data_*");

            return;
        }

        $repair = $this->buildRepairRows($dataRows, $manualInputRows, $analysis);

        if ($repair === null) {
            $result->skipped++;
            $result->addMessage(sprintf(
                'Skipped %d (%s): prefix rows diverge from data_* (manual review)',
                $moduleId,
                get_the_title($moduleId),
            ));

            return;
        }

        $beforeCount = count($manualInputRows);
        $afterCount = count($repair['rows']);
        $action = $repair['action'];

        if ($this->dryRun) {
            $result->migrated++;
            $result->addMessage(sprintf(
                'Would repair mod-manualinput %d (%s): %s, rows %d → %d',
                $moduleId,
                get_the_title($moduleId),
                $action,
                $beforeCount,
                $afterCount,
            ));

            return;
        }

        update_field(self::TARGET_REPEATER, $repair['rows'], $moduleId);

        $afterRows = $this->readManualInputRows($moduleId);
        $remainingGaps = $this->analyzeGaps($dataRows, $afterRows);

        if ($remainingGaps['hasRowLoss'] || $remainingGaps['hasFieldLoss']) {
            $result->errors++;
            $result->addMessage(sprintf(
                'Failed %d (%s): gaps remain after repair attempt (%s)',
                $moduleId,
                get_the_title($moduleId),
                $action,
            ));

            return;
        }

        $result->migrated++;
        $result->addMessage(sprintf(
            'Repaired mod-manualinput %d (%s): %s, rows %d → %d',
            $moduleId,
            get_the_title($moduleId),
            $action,
            $beforeCount,
            count($afterRows),
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readDataRows(int $postId): array
    {
        $count = (int) get_post_meta($postId, self::LEGACY_REPEATER_COUNT, true);

        if ($count <= 0) {
            return [];
        }

        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $row = [
                'post_title' => (string) get_post_meta($postId, "data_{$i}_post_title", true),
                'post_content' => (string) get_post_meta($postId, "data_{$i}_post_content", true),
                'permalink' => (string) get_post_meta($postId, "data_{$i}_permalink", true),
                'image' => get_post_meta($postId, "data_{$i}_image", true),
                'item_icon' => (string) get_post_meta($postId, "data_{$i}_item_icon", true),
            ];

            $columnCount = (int) get_post_meta($postId, "data_{$i}_column_values", true);

            if ($columnCount > 0) {
                $columns = [];

                for ($j = 0; $j < $columnCount; $j++) {
                    $columns[] = [
                        'value' => (string) get_post_meta(
                            $postId,
                            "data_{$i}_column_values_{$j}_value",
                            true,
                        ),
                    ];
                }

                $row['column_values'] = $columns;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readManualInputRows(int $postId): array
    {
        $field = get_field(self::TARGET_REPEATER, $postId);

        if (is_array($field) && $field !== []) {
            return $field;
        }

        $count = (int) get_post_meta($postId, self::TARGET_REPEATER, true);

        if ($count <= 0) {
            return [];
        }

        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'title' => get_post_meta($postId, "manual_inputs_{$i}_title", true),
                'content' => get_post_meta($postId, "manual_inputs_{$i}_content", true),
                'link' => get_post_meta($postId, "manual_inputs_{$i}_link", true),
                'image' => get_post_meta($postId, "manual_inputs_{$i}_image", true),
                'box_icon' => get_post_meta($postId, "manual_inputs_{$i}_box_icon", true),
                'accordion_column_values' => $this->readAccordionColumnsFromMeta($postId, $i),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array{value: string}>
     */
    private function readAccordionColumnsFromMeta(int $postId, int $rowIndex): array
    {
        $count = (int) get_post_meta($postId, "manual_inputs_{$rowIndex}_accordion_column_values", true);

        if ($count <= 0) {
            return [];
        }

        $columns = [];

        for ($j = 0; $j < $count; $j++) {
            $columns[] = [
                'value' => (string) get_post_meta(
                    $postId,
                    "manual_inputs_{$rowIndex}_accordion_column_values_{$j}_value",
                    true,
                ),
            ];
        }

        return $columns;
    }

    /**
     * @param array<int, array<string, mixed>> $dataRows
     */
    private function hasLegacyDataContent(array $dataRows): bool
    {
        foreach ($dataRows as $row) {
            if ($this->rowHasLegacyContent($row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowHasLegacyContent(array $row): bool
    {
        if (($row['post_title'] ?? '') !== '') {
            return true;
        }

        if (($row['post_content'] ?? '') !== '') {
            return true;
        }

        if (!$this->isEmptyField($row['permalink'] ?? null)) {
            return true;
        }

        if (!$this->isEmptyField($row['image'] ?? null)) {
            return true;
        }

        if (!$this->isEmptyField($row['item_icon'] ?? null)) {
            return true;
        }

        return !$this->isEmptyAccordionColumns($row['column_values'] ?? null);
    }

    /**
     * @param array<int, array<string, mixed>> $dataRows
     * @param array<int, array<string, mixed>> $manualInputRows
     *
     * @return array{hasRowLoss: bool, hasFieldLoss: bool}
     */
    private function analyzeGaps(array $dataRows, array $manualInputRows): array
    {
        $dataCount = count($dataRows);
        $miCount = count($manualInputRows);
        $hasRowLoss = false;
        $hasFieldLoss = false;

        if ($dataCount > $miCount) {
            for ($i = $miCount; $i < $dataCount; $i++) {
                $title = $dataRows[$i]['post_title'] ?? '';
                $content = $dataRows[$i]['post_content'] ?? '';

                if ($title !== '' || $content !== '') {
                    $hasRowLoss = true;
                    break;
                }
            }
        }

        $overlap = min($dataCount, $miCount);

        for ($i = 0; $i < $dataCount; $i++) {
            $dataRow = $dataRows[$i];
            $miRow = $i < $overlap ? $manualInputRows[$i] : [];

            if ($this->hasFieldGap($dataRow, $miRow)) {
                $hasFieldLoss = true;
                break;
            }
        }

        return [
            'hasRowLoss' => $hasRowLoss,
            'hasFieldLoss' => $hasFieldLoss,
        ];
    }

    /**
     * @param array<string, mixed> $dataRow
     * @param array<string, mixed> $miRow
     */
    private function hasFieldGap(array $dataRow, array $miRow): bool
    {
        if (!$this->isEmptyField($dataRow['permalink'] ?? null)
            && $this->isEmptyField($miRow['link'] ?? null)) {
            return true;
        }

        if (!$this->isEmptyField($dataRow['image'] ?? null)
            && $this->isEmptyField($miRow['image'] ?? null)) {
            return true;
        }

        if (!$this->isEmptyAccordionColumns($dataRow['column_values'] ?? null)
            && $this->isEmptyAccordionColumns($miRow['accordion_column_values'] ?? null)) {
            return true;
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $dataRows
     * @param array<int, array<string, mixed>> $manualInputRows
     * @param array{hasRowLoss: bool, hasFieldLoss: bool} $analysis
     *
     * @return array{action: string, rows: array<int, array<string, mixed>>}|null
     */
    private function buildRepairRows(array $dataRows, array $manualInputRows, array $analysis): ?array
    {
        if ($analysis['hasRowLoss']) {
            $prefixLength = count($manualInputRows);

            if ($this->prefixRowsMatch($dataRows, $manualInputRows, $prefixLength)) {
                $rows = [];

                foreach ($dataRows as $dataRow) {
                    $rows[] = $this->mapDataRowToManualInput($dataRow);
                }

                for ($i = count($dataRows); $i < count($manualInputRows); $i++) {
                    $rows[] = $manualInputRows[$i];
                }

                return [
                    'action' => 'rebuild from data_*',
                    'rows' => $rows,
                ];
            }

            if ($this->prefixTitlesMatch($dataRows, $manualInputRows, $prefixLength)) {
                $rows = $manualInputRows;

                for ($i = $prefixLength; $i < count($dataRows); $i++) {
                    $rows[] = $this->mapDataRowToManualInput($dataRows[$i]);
                }

                return [
                    'action' => 'append missing rows from data_*',
                    'rows' => $rows,
                ];
            }

            return null;
        }

        $rows = [];

        for ($i = 0; $i < count($dataRows); $i++) {
            $existingRow = $manualInputRows[$i] ?? [];
            $rows[] = $this->mergeRowFields($existingRow, $dataRows[$i]);
        }

        for ($i = count($dataRows); $i < count($manualInputRows); $i++) {
            $rows[] = $manualInputRows[$i];
        }

        return [
            'action' => 'merge missing fields from data_*',
            'rows' => $rows,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $dataRows
     * @param array<int, array<string, mixed>> $manualInputRows
     */
    private function prefixRowsMatch(array $dataRows, array $manualInputRows, int $prefixLength): bool
    {
        for ($i = 0; $i < $prefixLength; $i++) {
            $dataTitle = (string) ($dataRows[$i]['post_title'] ?? '');
            $miTitle = (string) ($manualInputRows[$i]['title'] ?? '');

            if ($dataTitle !== $miTitle) {
                return false;
            }

            $dataContent = (string) ($dataRows[$i]['post_content'] ?? '');
            $miContent = (string) ($manualInputRows[$i]['content'] ?? '');

            if ($miContent === '') {
                continue;
            }

            if ($this->normalizeContentForCompare($dataContent)
                !== $this->normalizeContentForCompare($miContent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $dataRows
     * @param array<int, array<string, mixed>> $manualInputRows
     */
    private function prefixTitlesMatch(array $dataRows, array $manualInputRows, int $prefixLength): bool
    {
        for ($i = 0; $i < $prefixLength; $i++) {
            $dataTitle = (string) ($dataRows[$i]['post_title'] ?? '');
            $miTitle = (string) ($manualInputRows[$i]['title'] ?? '');

            if ($dataTitle !== $miTitle) {
                return false;
            }
        }

        return true;
    }

    private function normalizeContentForCompare(string $content): string
    {
        $normalized = str_replace(['<br />', '<br/>', '<br>'], "\n", $content);
        $normalized = wp_strip_all_tags($normalized);
        $normalized = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized));

        return $normalized ?? '';
    }

    /**
     * @param array<string, mixed> $existingRow
     * @param array<string, mixed> $dataRow
     *
     * @return array<string, mixed>
     */
    private function mergeRowFields(array $existingRow, array $dataRow): array
    {
        $mapped = $this->mapDataRowToManualInput($dataRow);
        $merged = $existingRow;

        foreach (['title', 'content', 'link', 'image', 'box_icon'] as $field) {
            if ($this->isEmptyField($merged[$field] ?? null)
                && !$this->isEmptyField($mapped[$field] ?? null)) {
                $merged[$field] = $mapped[$field];
            }
        }

        if ($this->isEmptyAccordionColumns($merged['accordion_column_values'] ?? null)
            && !$this->isEmptyAccordionColumns($mapped['accordion_column_values'] ?? null)) {
            $merged['accordion_column_values'] = $mapped['accordion_column_values'];
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $dataRow
     *
     * @return array<string, mixed>
     */
    private function mapDataRowToManualInput(array $dataRow): array
    {
        $mapped = [
            'title' => (string) ($dataRow['post_title'] ?? ''),
            'content' => (string) ($dataRow['post_content'] ?? ''),
            'link' => (string) ($dataRow['permalink'] ?? ''),
        ];

        if (!$this->isEmptyField($dataRow['image'] ?? null)) {
            $mapped['image'] = (int) $dataRow['image'];
        }

        if (!$this->isEmptyField($dataRow['item_icon'] ?? null)) {
            $mapped['box_icon'] = (string) $dataRow['item_icon'];
        }

        if (!$this->isEmptyAccordionColumns($dataRow['column_values'] ?? null)) {
            $mapped['accordion_column_values'] = $dataRow['column_values'];
        }

        return $mapped;
    }

    private function isEmptyField(mixed $value): bool
    {
        if ($value === null || $value === false || $value === '') {
            return true;
        }

        if (is_array($value) && $value === []) {
            return true;
        }

        return false;
    }

    private function isEmptyAccordionColumns(mixed $value): bool
    {
        if (!is_array($value) || $value === []) {
            return true;
        }

        foreach ($value as $column) {
            if (is_array($column) && ($column['value'] ?? '') !== '') {
                return false;
            }
        }

        return true;
    }
}
