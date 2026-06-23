<?php

namespace App\Services\Scoring;

use App\Models\Application;
use App\Models\ApplicationScore;
use App\Models\RankingSnapshot;
use App\Models\ScoringRuleSet;
use App\Models\ScoringRun;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

class RankingService
{
    public function __construct(
        private readonly TieBreakerService $tieBreakerService,
        private readonly RankingSnapshotService $snapshotService,
    ) {}

    public function rankRun(ScoringRun $run, User $actor): RankingSnapshot
    {
        $run->loadMissing('ruleSet.tieBreakerRules');
        $ruleSet = $run->ruleSet;

        if (! $ruleSet instanceof ScoringRuleSet) {
            throw new RuntimeException('Execução de pontuação sem matriz de classificação associada.');
        }

        $scores = $run->applicationScores()->with('application')->get();

        $scores->each(function (ApplicationScore $score) use ($ruleSet): void {
            $application = $score->application;

            if (! $application instanceof Application) {
                throw new RuntimeException('Pontuação sem candidatura associada.');
            }

            $score->forceFill([
                'tie_breaker_values' => $this->tieBreakerService->valuesFor($application, $ruleSet),
            ])->save();
        });

        $rankable = $scores
            ->reject(fn (ApplicationScore $score) => $score->excluded_from_ranking)
            ->sort(fn (ApplicationScore $left, ApplicationScore $right) => $this->compare($left, $right))
            ->values();

        $ties = $rankable
            ->groupBy(fn (ApplicationScore $score) => $this->signature($score))
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->keys()
            ->all();

        $previousSignature = null;
        $previousRank = null;

        foreach ($rankable as $index => $score) {
            $signature = $this->signature($score);
            $rank = $signature === $previousSignature ? $previousRank : $index + 1;

            $score->forceFill([
                'rank_position' => $rank,
                'is_tied' => in_array($signature, $ties, true),
            ])->save();

            $previousSignature = $signature;
            $previousRank = $rank;
        }

        $scores
            ->where('excluded_from_ranking', true)
            ->each(fn (ApplicationScore $score): bool => $score->forceFill([
                'rank_position' => null,
                'is_tied' => false,
            ])->save());

        return $this->snapshotService->createForRun(
            $run,
            $run->applicationScores()
                ->with('application')
                ->orderByRaw('rank_position is null, rank_position asc')
                ->orderBy('id')
                ->get(),
            $actor,
        );
    }

    private function compare(ApplicationScore $left, ApplicationScore $right): int
    {
        $scoreComparison = (float) $right->total_score <=> (float) $left->total_score;

        if ($scoreComparison !== 0) {
            return $scoreComparison;
        }

        foreach (($left->tie_breaker_values ?? []) as $index => $leftTieBreaker) {
            $rightTieBreaker = ($right->tie_breaker_values ?? [])[$index] ?? null;
            $comparison = $this->compareValues(
                $leftTieBreaker['value'] ?? null,
                $rightTieBreaker['value'] ?? null,
            );

            if ($comparison !== 0) {
                return ($leftTieBreaker['direction'] ?? 'asc') === 'desc' ? -$comparison : $comparison;
            }
        }

        return $left->id <=> $right->id;
    }

    private function compareValues(mixed $left, mixed $right): int
    {
        if ($left === $right) {
            return 0;
        }

        if ($left === null) {
            return 1;
        }

        if ($right === null) {
            return -1;
        }

        return $left <=> $right;
    }

    private function signature(ApplicationScore $score): string
    {
        $tieBreakerValues = collect($score->tie_breaker_values ?? [])
            ->map(fn (array $tieBreaker) => [
                'code' => $tieBreaker['code'] ?? null,
                'value' => $tieBreaker['value'] ?? null,
            ])
            ->values()
            ->all();

        $encoded = json_encode([
            'total_score' => (string) $score->total_score,
            'tie_breakers' => $tieBreakerValues,
        ]);

        return md5(is_string($encoded) ? $encoded : '');
    }
}
