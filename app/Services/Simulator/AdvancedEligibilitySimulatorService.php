<?php

namespace App\Services\Simulator;

use App\Enums\SimulationResultStatus;
use App\Enums\SimulationScope;
use App\Models\Contest;
use App\Models\SimulationSession;
use App\Models\User;
use Illuminate\Http\Request;

class AdvancedEligibilitySimulatorService
{
    public function __construct(
        private readonly SimulationInputBuilder $inputBuilder,
        private readonly SimulationDataCompletenessService $completenessService,
        private readonly TypologyRecommendationService $typologyService,
        private readonly RentEstimateService $rentService,
        private readonly SimulationImpedimentDetector $impedimentDetector,
        private readonly ContestRecommendationService $contestRecommendationService,
        private readonly SimulationSessionService $sessionService,
        private readonly SimulationResultService $resultService,
        private readonly SimulationAuditService $auditService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function simulateAnonymous(array $data, Request $request): SimulationSession
    {
        $input = $this->inputBuilder->fromRequest($data);

        return $this->run(SimulationScope::Anonymous, $input, $request);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function simulateForUser(User $user, array $data, Request $request): SimulationSession
    {
        $input = $this->inputBuilder->fromUser($user, $data);
        $session = $this->run(SimulationScope::Authenticated, $input, $request, $user);

        $this->auditService->record($user, $session, 'create', 'Simulação avançada criada pelo candidato.');

        return $session;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function run(SimulationScope $scope, array $input, Request $request, ?User $user = null): SimulationSession
    {
        $contest = $this->contest($input);
        $completeness = $this->completenessService->evaluate($input);
        $session = $this->sessionService->create($scope, $input, $completeness, $request, $user);
        $typology = $this->typologyService->recommend($input, $contest);
        $rent = $this->rentService->estimate($input, $contest);
        $impediments = $this->impedimentDetector->detect($input, $completeness, $contest, $user);
        $recommendations = $this->contestRecommendationService->recommend($input, $typology, $rent, $contest);
        $status = $this->status($completeness, $impediments, $recommendations);

        $this->resultService->persist($session, $status, $completeness, $typology, $rent, $impediments, $recommendations);

        return $session->fresh(['inputSnapshot', 'result.impediments', 'result.recommendedContests.contest.program', 'recommendedContests.contest.program']) ?? $session;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function contest(array $input): ?Contest
    {
        if (! is_numeric($input['contest_id'] ?? null)) {
            return null;
        }

        return Contest::query()
            ->with(['program', 'contestHousingUnits'])
            ->find((int) $input['contest_id']);
    }

    /**
     * @param  array{score: float, missing_fields: list<string>, complete: bool}  $completeness
     * @param  list<array<string, mixed>>  $impediments
     * @param  list<array<string, mixed>>  $recommendations
     */
    private function status(array $completeness, array $impediments, array $recommendations): SimulationResultStatus
    {
        $blocking = array_filter($impediments, static fn (array $item): bool => (bool) ($item['is_blocking'] ?? false));

        if ($blocking !== []) {
            return SimulationResultStatus::LikelyIneligible;
        }

        if ($completeness['score'] < 75) {
            return SimulationResultStatus::InsufficientData;
        }

        if ($recommendations === []) {
            return SimulationResultStatus::NoMatchingContest;
        }

        $reviews = array_filter($impediments, static fn (array $item): bool => ($item['severity'] ?? null) === 'requires_review');

        return $reviews !== []
            ? SimulationResultStatus::RequiresReview
            : SimulationResultStatus::LikelyEligible;
    }
}
