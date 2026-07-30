<?php

namespace App\Services\Contests;

use App\Enums\ContestApplicationPhase;
use App\Enums\ContestDeadlineType;
use App\Enums\ContestStatus;
use App\Models\Contest;
use App\Models\ContestDeadline;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

class ContestApplicationPhaseService
{
    public function __construct(
        private readonly ContestApplicationTimelineService $timeline,
    ) {}

    public function isOpenForApplications(
        Contest $contest,
        DateTimeInterface|string|null $at = null,
    ): bool {
        if ($contest->status !== ContestStatus::Published) {
            return false;
        }

        $reference = $this->reference($at);

        if ($contest->published_at === null || $reference->lessThan($contest->published_at)) {
            return false;
        }

        $deadline = $this->deadline($contest, ContestDeadlineType::Applications);

        return $deadline !== null
            && $deadline->starts_at !== null
            && $reference->betweenIncluded($deadline->starts_at, $deadline->ends_at);
    }

    public function publicPhase(
        Contest $contest,
        DateTimeInterface|string|null $at = null,
    ): string {
        if ($contest->status === ContestStatus::Cancelled) {
            return 'cancelled';
        }

        $deadline = $this->deadline($contest, ContestDeadlineType::Applications);

        if ($deadline === null || $deadline->starts_at === null) {
            return 'upcoming';
        }

        $reference = $this->reference($at);

        if ($reference->lessThan($deadline->starts_at)) {
            return 'upcoming';
        }

        if ($reference->greaterThan($deadline->ends_at)) {
            return 'closed';
        }

        return $this->isOpenForApplications($contest, $reference)
            ? 'open'
            : 'upcoming';
    }

    public function current(
        Contest $contest,
        DateTimeInterface|string|null $at = null,
    ): ContestApplicationPhase {
        if ($contest->status === ContestStatus::Cancelled) {
            return ContestApplicationPhase::Cancelled;
        }

        if ($contest->status !== ContestStatus::Published) {
            return ContestApplicationPhase::Upcoming;
        }

        $reference = $this->reference($at);
        $deadlines = $this->processingDeadlines($contest);
        $applications = $deadlines->first(
            fn (ContestDeadline $deadline): bool => $deadline->type === ContestDeadlineType::Applications,
        );

        if ($applications === null || $applications->starts_at === null) {
            return ContestApplicationPhase::Upcoming;
        }

        if ($reference->lessThan($applications->starts_at)) {
            return ContestApplicationPhase::Upcoming;
        }

        foreach ($deadlines as $deadline) {
            if (
                $deadline->starts_at !== null
                && $reference->betweenIncluded($deadline->starts_at, $deadline->ends_at)
            ) {
                return ContestApplicationPhase::fromDeadlineType($deadline->type)
                    ?? ContestApplicationPhase::BetweenPhases;
            }
        }

        if ($deadlines->contains(
            fn (ContestDeadline $deadline): bool => $deadline->starts_at?->isAfter($reference) === true,
        )) {
            return ContestApplicationPhase::BetweenPhases;
        }

        return ContestApplicationPhase::Completed;
    }

    public function nextDeadline(
        Contest $contest,
        DateTimeInterface|string|null $at = null,
    ): ?ContestDeadline {
        $reference = $this->reference($at);

        return $this->processingDeadlines($contest)
            ->first(
                fn (ContestDeadline $deadline): bool => $deadline->starts_at?->isAfter($reference) === true,
            );
    }

    /**
     * @return array{
     *     current: ContestApplicationPhase,
     *     next_deadline: ContestDeadline|null,
     *     next_phase: ContestApplicationPhase|null,
     *     readiness: array{complete: bool, missing: list<ContestDeadlineType>}
     * }
     */
    public function context(
        Contest $contest,
        DateTimeInterface|string|null $at = null,
    ): array {
        $nextDeadline = $this->nextDeadline($contest, $at);

        return [
            'current' => $this->current($contest, $at),
            'next_deadline' => $nextDeadline,
            'next_phase' => $nextDeadline === null
                ? null
                : ContestApplicationPhase::fromDeadlineType($nextDeadline->type),
            'readiness' => $this->timeline->readiness($contest),
        ];
    }

    public function deadline(
        Contest $contest,
        ContestDeadlineType $type,
    ): ?ContestDeadline {
        $deadline = $this->deadlines($contest)
            ->first(fn (ContestDeadline $deadline): bool => $deadline->type === $type);

        if ($type !== ContestDeadlineType::Applications) {
            return $deadline;
        }

        if (
            $deadline !== null
            && $deadline->starts_at !== null
        ) {
            return $deadline;
        }

        if ($contest->opens_at === null || $contest->closes_at === null) {
            return null;
        }

        return (new ContestDeadline)->forceFill([
            'contest_id' => $contest->getKey(),
            'type' => ContestDeadlineType::Applications->value,
            'label' => ContestDeadlineType::Applications->defaultLabel(),
            'starts_at' => $contest->opens_at,
            'ends_at' => $contest->closes_at,
            'description' => null,
            'sort_order' => 0,
        ]);
    }

    /**
     * @return Collection<int, ContestDeadline>
     */
    private function processingDeadlines(Contest $contest): Collection
    {
        $deadlines = $this->deadlines($contest)
            ->filter(fn (ContestDeadline $deadline): bool => $deadline->type->isApplicationProcessingPhase())
            ->values();

        if (! $deadlines->contains(
            fn (ContestDeadline $deadline): bool => $deadline->type === ContestDeadlineType::Applications,
        )) {
            $applicationDeadline = $this->deadline($contest, ContestDeadlineType::Applications);

            if ($applicationDeadline !== null) {
                $deadlines->push($applicationDeadline);
            }
        }

        return $deadlines
            ->sortBy(fn (ContestDeadline $deadline): int => $deadline->type->processingOrder() ?? PHP_INT_MAX)
            ->values();
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

    private function reference(DateTimeInterface|string|null $at): CarbonImmutable
    {
        $timezone = (string) config('app.timezone', 'Europe/Lisbon');

        if ($at instanceof DateTimeInterface) {
            return CarbonImmutable::instance($at)->setTimezone($timezone);
        }

        return $at === null
            ? CarbonImmutable::now($timezone)
            : CarbonImmutable::parse($at, $timezone);
    }
}
