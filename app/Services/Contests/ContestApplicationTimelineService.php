<?php

namespace App\Services\Contests;

use App\Enums\ContestDeadlineType;
use App\Models\Contest;
use App\Models\ContestDeadline;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ContestApplicationTimelineService
{
    /**
     * @param  list<array<string, mixed>>  $deadlines
     * @return list<array<string, mixed>>
     */
    public function normalize(
        mixed $opensAt,
        mixed $closesAt,
        array $deadlines,
    ): array {
        $applicationStartsAt = $this->date($opensAt, 'opens_at');
        $applicationEndsAt = $this->date($closesAt, 'closes_at');

        if ($applicationEndsAt->lessThanOrEqualTo($applicationStartsAt)) {
            throw ValidationException::withMessages([
                'closes_at' => 'O encerramento das candidaturas deve ser posterior à abertura.',
            ]);
        }

        $applicationMetadata = null;
        $processing = [];
        $other = [];

        foreach ($deadlines as $index => $deadline) {
            if ($this->isBlankRow($deadline)) {
                continue;
            }

            $type = $this->deadlineType($deadline['type'] ?? null, $index);
            $label = trim((string) ($deadline['label'] ?? ''));
            $endsAtValue = $deadline['ends_at'] ?? null;

            if ($label === '') {
                throw ValidationException::withMessages([
                    "deadlines.{$index}.label" => 'Indique a designação do prazo.',
                ]);
            }

            if (blank($endsAtValue)) {
                throw ValidationException::withMessages([
                    "deadlines.{$index}.ends_at" => 'Indique o fim do prazo.',
                ]);
            }

            if ($type === ContestDeadlineType::Applications) {
                if ($applicationMetadata !== null) {
                    throw ValidationException::withMessages([
                        "deadlines.{$index}.type" => 'Só pode existir um prazo de candidaturas.',
                    ]);
                }

                $applicationMetadata = $deadline;

                continue;
            }

            $startsAt = blank($deadline['starts_at'] ?? null)
                ? null
                : $this->date($deadline['starts_at'], "deadlines.{$index}.starts_at");
            $endsAt = $this->date($endsAtValue, "deadlines.{$index}.ends_at");

            if ($type->isApplicationProcessingPhase() && $startsAt === null) {
                throw ValidationException::withMessages([
                    "deadlines.{$index}.starts_at" => 'As fases processuais exigem data de início e de fim.',
                ]);
            }

            if ($startsAt !== null && $endsAt->lessThanOrEqualTo($startsAt)) {
                throw ValidationException::withMessages([
                    "deadlines.{$index}.ends_at" => 'O fim do prazo deve ser posterior ao início.',
                ]);
            }

            $normalized = [
                'type' => $type->value,
                'label' => $label,
                'starts_at' => $startsAt?->format('Y-m-d H:i:s'),
                'ends_at' => $endsAt->format('Y-m-d H:i:s'),
                'description' => filled($deadline['description'] ?? null)
                    ? trim((string) $deadline['description'])
                    : null,
            ];

            if ($type->isApplicationProcessingPhase()) {
                if (array_key_exists($type->value, $processing)) {
                    throw ValidationException::withMessages([
                        "deadlines.{$index}.type" => "Só pode existir uma fase «{$type->label()}».",
                    ]);
                }

                $processing[$type->value] = $normalized;
            } else {
                $other[] = $normalized;
            }
        }

        $application = [
            'type' => ContestDeadlineType::Applications->value,
            'label' => trim((string) ($applicationMetadata['label'] ?? ''))
                ?: ContestDeadlineType::Applications->defaultLabel(),
            'starts_at' => $applicationStartsAt->format('Y-m-d H:i:s'),
            'ends_at' => $applicationEndsAt->format('Y-m-d H:i:s'),
            'description' => filled($applicationMetadata['description'] ?? null)
                ? trim((string) $applicationMetadata['description'])
                : null,
        ];

        $processing = [
            ContestDeadlineType::Applications->value => $application,
            ...$processing,
        ];

        $this->assertProcessingSequence($processing);

        $ordered = [];

        foreach ($this->processingTypes() as $type) {
            if (isset($processing[$type->value])) {
                $ordered[] = $processing[$type->value];
            }
        }

        return [...$ordered, ...$other];
    }

    public function assertConfigured(Contest $contest): void
    {
        $deadlines = [];

        foreach ($this->deadlines($contest) as $deadline) {
            $deadlines[] = [
                'type' => $deadline->type->value,
                'label' => $deadline->label,
                'starts_at' => $deadline->starts_at,
                'ends_at' => $deadline->ends_at,
                'description' => $deadline->description,
            ];
        }

        $this->normalize(
            $contest->opens_at,
            $contest->closes_at,
            $deadlines,
        );
    }

    /**
     * @return array{complete: bool, missing: list<ContestDeadlineType>}
     */
    public function readiness(Contest $contest): array
    {
        $available = [];

        foreach ($this->deadlines($contest) as $deadline) {
            $available[] = $deadline->type->value;
        }

        if ($contest->opens_at !== null && $contest->closes_at !== null) {
            $available[] = ContestDeadlineType::Applications->value;
        }

        $missing = [];

        foreach ($this->processingTypes() as $type) {
            if (! in_array($type->value, $available, true)) {
                $missing[] = $type;
            }
        }

        return [
            'complete' => $missing === [],
            'missing' => $missing,
        ];
    }

    /**
     * @return Collection<int, ContestDeadline>
     */
    private function deadlines(Contest $contest): Collection
    {
        if ($contest->relationLoaded('deadlines')) {
            return $contest->deadlines;
        }

        return $contest->deadlines()->get();
    }

    /**
     * @param  array<string, array<string, mixed>>  $processing
     */
    private function assertProcessingSequence(array $processing): void
    {
        $previous = null;

        foreach ($this->processingTypes() as $type) {
            $deadline = $processing[$type->value] ?? null;

            if ($deadline === null) {
                continue;
            }

            $startsAt = $this->date($deadline['starts_at'], "deadlines.{$type->value}.starts_at");
            $endsAt = $this->date($deadline['ends_at'], "deadlines.{$type->value}.ends_at");

            if ($previous !== null && $startsAt->lessThanOrEqualTo($previous['ends_at'])) {
                throw ValidationException::withMessages([
                    'deadlines' => "A fase «{$type->label()}» não pode começar antes do fim da fase «{$previous['type']->label()}».",
                ]);
            }

            $previous = [
                'type' => $type,
                'ends_at' => $endsAt,
            ];
        }
    }

    /**
     * @return list<ContestDeadlineType>
     */
    private function processingTypes(): array
    {
        return [
            ContestDeadlineType::Applications,
            ContestDeadlineType::Review,
            ContestDeadlineType::Corrections,
            ContestDeadlineType::Revalidation,
        ];
    }

    /**
     * @param  array<string, mixed>  $deadline
     */
    private function isBlankRow(array $deadline): bool
    {
        return blank($deadline['type'] ?? null)
            && blank($deadline['label'] ?? null)
            && blank($deadline['starts_at'] ?? null)
            && blank($deadline['ends_at'] ?? null)
            && blank($deadline['description'] ?? null);
    }

    private function deadlineType(mixed $value, int $index): ContestDeadlineType
    {
        $type = $value instanceof ContestDeadlineType
            ? $value
            : ContestDeadlineType::tryFrom((string) $value);

        if ($type === null) {
            throw ValidationException::withMessages([
                "deadlines.{$index}.type" => 'Selecione um tipo de prazo válido.',
            ]);
        }

        return $type;
    }

    private function date(mixed $value, string $field): CarbonImmutable
    {
        try {
            if ($value instanceof DateTimeInterface) {
                return CarbonImmutable::instance($value)
                    ->setTimezone((string) config('app.timezone', 'Europe/Lisbon'));
            }

            return CarbonImmutable::parse(
                (string) $value,
                (string) config('app.timezone', 'Europe/Lisbon'),
            );
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => 'Indique uma data e hora válidas.',
            ]);
        }
    }
}
