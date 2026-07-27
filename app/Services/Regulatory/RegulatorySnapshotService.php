<?php

namespace App\Services\Regulatory;

use App\Enums\RegulatoryClassificationStatus;
use App\Enums\RegulatoryContext;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\AllocationRuleSet;
use App\Models\Application;
use App\Models\Contest;
use App\Models\Contract;
use App\Models\EligibilityRuleSet;
use App\Models\Program;
use App\Models\RegulatorySnapshot;
use App\Models\RentRuleSet;
use App\Models\TypologyAdequacyRule;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Regulatory\RentLimits\RentLimitProviderRegistry;
use App\Support\AuditEvents;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;

class RegulatorySnapshotService
{
    public function __construct(
        private readonly MunicipalRegulatoryOverlayService $overlayService,
        private readonly RentLimitProviderRegistry $rentLimitProviders,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function attach(
        Model $subject,
        AffordableRentRegulatoryProfile $profile,
        RegulatoryContext $context,
        CarbonInterface $referenceDate,
        ?User $actor,
        string $origin,
    ): RegulatorySnapshot {
        return DB::transaction(function () use ($subject, $profile, $context, $referenceDate, $actor, $origin): RegulatorySnapshot {
            $existing = $this->existing($subject, $context);

            if ($existing instanceof RegulatorySnapshot) {
                return $existing;
            }

            $profile->loadMissing('parentProfile');
            $parameters = $this->overlayService->effectiveParameters($profile);
            $ruleSets = $this->ruleSets($subject, $profile, $referenceDate);
            $rentRuleSet = isset($ruleSets['rent_rule_set_id'])
                ? RentRuleSet::query()->find($ruleSets['rent_rule_set_id'])
                : null;
            $rentLimits = $this->rentLimitProviders
                ->forProfile($profile)
                ->limitsFor($profile, $rentRuleSet, $referenceDate);
            $municipalOverlay = $this->overlayService->snapshot($profile);
            $limits = [
                'status' => $rentLimits->status->value,
                'minimum_rent' => $rentLimits->minimumRent,
                'maximum_rent' => $rentLimits->maximumRent,
                'source_version' => $rentLimits->sourceVersion,
                'parameters' => $rentLimits->parameters,
            ];

            return $this->persist(
                $subject,
                $profile,
                $context,
                $referenceDate,
                $actor,
                $origin,
                $ruleSets,
                $limits,
                $parameters,
                $municipalOverlay,
            );
        });
    }

    public function attachFromSnapshot(
        Model $subject,
        RegulatorySnapshot $sourceSnapshot,
        RegulatoryContext $context,
        CarbonInterface $referenceDate,
        ?User $actor,
        string $origin,
    ): RegulatorySnapshot {
        return DB::transaction(function () use ($subject, $sourceSnapshot, $context, $referenceDate, $actor, $origin): RegulatorySnapshot {
            $existing = $this->existing($subject, $context);

            if ($existing instanceof RegulatorySnapshot) {
                return $existing;
            }

            $sourceSnapshot->loadMissing('profile.parentProfile');
            $profile = $sourceSnapshot->getRelationValue('profile');

            if (! $profile instanceof AffordableRentRegulatoryProfile) {
                throw new \LogicException('O snapshot regulamentar de origem não possui perfil associado.');
            }

            return $this->persist(
                $subject,
                $profile,
                $context,
                $referenceDate,
                $actor,
                $origin,
                $sourceSnapshot->rule_sets ?? [],
                $sourceSnapshot->limits ?? [],
                $sourceSnapshot->parameters ?? [],
                $sourceSnapshot->municipal_overlay ?? [],
            );
        });
    }

    private function existing(Model $subject, RegulatoryContext $context): ?RegulatorySnapshot
    {
        return RegulatorySnapshot::query()
            ->where('source_type', $subject->getMorphClass())
            ->where('source_id', $subject->getKey())
            ->where('context', $context->value)
            ->first();
    }

    /**
     * @param  array<string, int>  $ruleSets
     * @param  array<string, mixed>  $limits
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $municipalOverlay
     */
    private function persist(
        Model $subject,
        AffordableRentRegulatoryProfile $profile,
        RegulatoryContext $context,
        CarbonInterface $referenceDate,
        ?User $actor,
        string $origin,
        array $ruleSets,
        array $limits,
        array $parameters,
        array $municipalOverlay,
    ): RegulatorySnapshot {
        $municipalityId = $this->contextMunicipalityId($subject, $profile);

        if (
            $profile->municipality_id !== null
            && $municipalityId !== null
            && $profile->municipality_id !== $municipalityId
        ) {
            throw ValidationException::withMessages([
                'regulatory_profile_id' => 'O perfil regulamentar pertence a outro Município.',
            ]);
        }

        $payload = [
            'municipality_id' => $municipalityId,
            'regulatory_profile_id' => $profile->id,
            'legal_regime' => $profile->legal_regime->value,
            'context' => $context->value,
            'source_type' => $subject->getMorphClass(),
            'source_id' => $subject->getKey(),
            'reference_date' => $referenceDate->toIso8601String(),
            'profile_code' => $profile->code,
            'profile_version' => $profile->version,
            'legal_basis' => $profile->legal_basis,
            'rule_sets' => $ruleSets,
            'limits' => $limits,
            'parameters' => $parameters,
            'municipal_overlay' => $municipalOverlay,
            'origin' => $origin,
        ];

        $snapshot = RegulatorySnapshot::query()->create([
            ...$payload,
            'checksum' => hash('sha256', $this->canonicalJson($payload)),
            'created_by' => $actor?->id,
            'locked_at' => now(),
        ]);

        $attributes = [
            'regulatory_snapshot_id' => $snapshot->id,
            'legal_regime' => $profile->legal_regime->value,
        ];

        if ($subject instanceof Program || $subject instanceof Contest) {
            $attributes['regulatory_profile_id'] = $profile->id;
        }

        if ($subject instanceof Contract) {
            $attributes['regulatory_classification_status'] = RegulatoryClassificationStatus::Configured->value;
        }

        $subject->forceFill($attributes)->save();

        $this->auditLogger->record(
            event: AuditEvents::CREATE,
            auditable: $snapshot,
            module: 'regulatory',
            action: 'snapshot_lock',
            description: 'Snapshot regulamentar criado e bloqueado.',
            metadata: [
                'context' => $context->value,
                'source_type' => class_basename($subject),
                'source_id' => $subject->getKey(),
                'profile_id' => $profile->id,
                'checksum' => $snapshot->checksum,
            ],
        );

        return $snapshot;
    }

    /**
     * @return array<string, int>
     */
    private function ruleSets(
        Model $subject,
        AffordableRentRegulatoryProfile $profile,
        CarbonInterface $referenceDate,
    ): array {
        [$programId, $contestId] = $this->contextIds($subject);
        $context = function ($query) use ($programId, $contestId): void {
            $query->when(
                $contestId !== null,
                fn ($builder) => $builder
                    ->where('contest_id', $contestId)
                    ->orWhere(fn ($fallback) => $fallback
                        ->whereNull('contest_id')
                        ->where('program_id', $programId)),
                fn ($builder) => $builder
                    ->whereNull('contest_id')
                    ->where('program_id', $programId),
            );
        };

        $ids = [];
        $eligibility = EligibilityRuleSet::query()
            ->activeAt($referenceDate)
            ->where('regulatory_profile_id', $profile->id)
            ->where($context)
            ->orderByRaw('case when contest_id is null then 1 else 0 end')
            ->latest('id')
            ->first();
        $rent = RentRuleSet::query()
            ->activeAt($referenceDate)
            ->where('regulatory_profile_id', $profile->id)
            ->where($context)
            ->orderByRaw('case when contest_id is null then 1 else 0 end')
            ->latest('id')
            ->first();
        $typology = TypologyAdequacyRule::query()
            ->active()
            ->where('regulatory_profile_id', $profile->id)
            ->where($context)
            ->orderByRaw('case when contest_id is null then 1 else 0 end')
            ->orderBy('priority_order')
            ->first();
        $allocation = AllocationRuleSet::query()
            ->active()
            ->where('regulatory_profile_id', $profile->id)
            ->where($context)
            ->orderByRaw('case when contest_id is null then 1 else 0 end')
            ->latest('id')
            ->first();

        foreach ([
            'eligibility_rule_set_id' => $eligibility?->id,
            'rent_rule_set_id' => $rent?->id,
            'typology_rule_id' => $typology?->id,
            'allocation_rule_set_id' => $allocation?->id,
        ] as $key => $id) {
            if ($id !== null) {
                $ids[$key] = $id;
            }
        }

        return $ids;
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function contextIds(Model $subject): array
    {
        if ($subject instanceof Program) {
            return [$subject->id, null];
        }

        if ($subject instanceof Contest) {
            return [$subject->program_id, $subject->id];
        }

        $programId = $this->nullableInt($subject->getAttribute('program_id'));
        $contestId = $this->nullableInt($subject->getAttribute('contest_id'));
        $applicationId = $this->nullableInt($subject->getAttribute('application_id'));

        if (($programId === null || $contestId === null) && $applicationId !== null) {
            $application = Application::query()
                ->select(['id', 'program_id', 'contest_id'])
                ->find($applicationId);
            $programId ??= $application?->program_id;
            $contestId ??= $application?->contest_id;
        }

        return [$programId, $contestId];
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function contextMunicipalityId(
        Model $subject,
        AffordableRentRegulatoryProfile $profile,
    ): ?int {
        $directMunicipalityId = $this->nullableInt(
            $subject->getAttribute('municipality_id'),
        );

        if ($directMunicipalityId !== null) {
            return $directMunicipalityId;
        }

        [$programId] = $this->contextIds($subject);

        if ($programId !== null) {
            $municipalityId = Program::query()
                ->whereKey($programId)
                ->value('municipality_id');

            if (is_numeric($municipalityId)) {
                return (int) $municipalityId;
            }
        }

        return $profile->municipality_id;
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws JsonException
     */
    private function canonicalJson(array $payload): string
    {
        $normalized = $this->sortRecursively($payload);

        return json_encode(
            $normalized,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private function sortRecursively(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->sortRecursively($item);
        }

        return $value;
    }
}
