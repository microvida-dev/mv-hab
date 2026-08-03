<?php

namespace App\Services\Reporting\Temporal;

use App\Enums\ApplicationResultChangeType;
use Generator;
use Iterator;
use IteratorAggregate;
use RuntimeException;

final class ApplicationResultExportComparator
{
    public function __construct(
        private readonly ApplicationResultExportFieldCatalog $catalog,
    ) {}

    /**
     * Inputs must be sorted by the same stable key.
     *
     * @param  iterable<array<string, mixed>>  $baseRows
     * @param  iterable<array<string, mixed>>  $targetRows
     * @param  list<string>  $keyFields
     * @param  list<string>  $comparedFields
     * @return Generator<int, array<string, mixed>>
     */
    public function compare(
        iterable $baseRows,
        iterable $targetRows,
        string $entityType,
        array $keyFields,
        array $comparedFields,
        string $beforeSource,
        string $afterSource,
        string $changedAt,
        bool $includeSensitive = false,
        bool $includeUnchanged = false,
    ): Generator {
        $base = $this->iterator($baseRows);
        $target = $this->iterator($targetRows);
        $base->rewind();
        $target->rewind();

        $lastBaseKey = null;
        $lastTargetKey = null;

        while ($base->valid() || $target->valid()) {
            $before = $base->valid() ? $this->row($base->current()) : null;
            $after = $target->valid() ? $this->row($target->current()) : null;
            $beforeKey = $before === null ? null : $this->key($before, $keyFields);
            $afterKey = $after === null ? null : $this->key($after, $keyFields);

            $this->assertUniqueKey($beforeKey, $lastBaseKey, 'base');
            $this->assertUniqueKey($afterKey, $lastTargetKey, 'target');

            if ($beforeKey !== null && ($afterKey === null || $beforeKey < $afterKey)) {
                yield $this->change(
                    $entityType,
                    $beforeKey,
                    $before,
                    ApplicationResultChangeType::Removed,
                    null,
                    $this->safeEntityValue($before, $includeSensitive),
                    null,
                    $beforeSource,
                    $afterSource,
                    $changedAt,
                    false,
                );
                $lastBaseKey = $beforeKey;
                $base->next();

                continue;
            }

            if ($afterKey !== null && ($beforeKey === null || $afterKey < $beforeKey)) {
                yield $this->change(
                    $entityType,
                    $afterKey,
                    $after,
                    ApplicationResultChangeType::Added,
                    null,
                    null,
                    $this->safeEntityValue($after, $includeSensitive),
                    $beforeSource,
                    $afterSource,
                    $changedAt,
                    false,
                );
                $lastTargetKey = $afterKey;
                $target->next();

                continue;
            }

            if ($before === null || $after === null || $beforeKey === null) {
                throw new RuntimeException('A comparação temporal perdeu a correspondência canónica.');
            }

            $changed = false;
            foreach ($comparedFields as $fieldCode) {
                $beforeValue = $before[$fieldCode] ?? null;
                $afterValue = $after[$fieldCode] ?? null;
                if ($this->equal($beforeValue, $afterValue)) {
                    continue;
                }

                $changed = true;
                $field = $this->catalog->find($fieldCode);
                $redacted = $field !== null
                    && ! $field->sensitivity->includedByDefault()
                    && ! ($includeSensitive
                        && $field->sensitivity->canBeIncludedInSensitiveExport());

                yield $this->change(
                    $entityType,
                    $beforeKey,
                    $after,
                    ApplicationResultChangeType::Changed,
                    $fieldCode,
                    $redacted ? '[VALOR OCULTADO]' : $beforeValue,
                    $redacted ? '[VALOR OCULTADO]' : $afterValue,
                    $beforeSource,
                    $afterSource,
                    $changedAt,
                    $redacted,
                );
            }

            if (! $changed && $includeUnchanged) {
                yield $this->change(
                    $entityType,
                    $beforeKey,
                    $after,
                    ApplicationResultChangeType::Unchanged,
                    null,
                    null,
                    null,
                    $beforeSource,
                    $afterSource,
                    $changedAt,
                    false,
                );
            }

            $lastBaseKey = $beforeKey;
            $lastTargetKey = $afterKey;
            $base->next();
            $target->next();
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keyFields
     */
    private function key(array $row, array $keyFields): string
    {
        if ($keyFields === []) {
            throw new RuntimeException(
                'Uma entidade temporal nao possui campos de chave configurados.',
            );
        }

        $parts = [];
        foreach ($keyFields as $field) {
            $value = $row[$field] ?? null;
            $parts[] = $value === null
                ? '<null>'
                : str_replace(['\\', '|'], ['\\\\', '\\|'], (string) $value);
        }

        return implode('|', $parts);
    }

    private function assertUniqueKey(
        ?string $key,
        ?string $lastKey,
        string $source,
    ): void {
        if ($key !== null && $key === $lastKey) {
            throw new RuntimeException(
                "A fonte {$source} contém uma chave temporal ambígua.",
            );
        }
    }

    private function equal(mixed $before, mixed $after): bool
    {
        if (is_array($before) || is_array($after)) {
            return json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                === json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $before === $after;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function safeEntityValue(array $row, bool $includeSensitive): array
    {
        foreach ($row as $code => $value) {
            $field = $this->catalog->find($code);
            if (
                $field !== null
                && ! $field->sensitivity->includedByDefault()
                && ! ($includeSensitive
                    && $field->sensitivity->canBeIncludedInSensitiveExport())
            ) {
                $row[$code] = '[VALOR OCULTADO]';
            }
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>|null  $row
     * @return array<string, mixed>
     */
    private function change(
        string $entityType,
        string $entityReference,
        ?array $row,
        ApplicationResultChangeType $type,
        ?string $fieldCode,
        mixed $before,
        mixed $after,
        string $beforeSource,
        string $afterSource,
        string $changedAt,
        bool $redacted,
    ): array {
        return [
            'entity_type' => $entityType,
            'entity_reference' => $entityReference,
            'application_number' => $row['application_number'] ?? null,
            'change_type' => $type->value,
            'field_code' => $fieldCode,
            'before_value' => $before,
            'after_value' => $after,
            'before_source' => $beforeSource,
            'after_source' => $afterSource,
            'changed_at' => $changedAt,
            'sensitive_value_redacted' => $redacted,
        ];
    }

    /** @param iterable<array<string, mixed>> $rows */
    private function iterator(iterable $rows): Iterator
    {
        if ($rows instanceof Iterator) {
            return $rows;
        }

        if ($rows instanceof IteratorAggregate) {
            $iterator = $rows->getIterator();
            if ($iterator instanceof Iterator) {
                return $iterator;
            }
        }

        return (function () use ($rows): Generator {
            foreach ($rows as $row) {
                yield $row;
            }
        })();
    }

    /** @return array<string, mixed> */
    private function row(mixed $row): array
    {
        if (! is_array($row)) {
            throw new RuntimeException('A fonte temporal contém uma linha inválida.');
        }

        return $row;
    }
}
