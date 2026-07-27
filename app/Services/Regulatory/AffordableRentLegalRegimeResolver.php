<?php

namespace App\Services\Regulatory;

use App\Data\Regulatory\LegalRegimeResolution;
use App\Enums\AffordableRentLegalRegime;
use App\Enums\LegalRegimeResolutionStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Application;
use App\Models\Contest;
use App\Models\Contract;
use App\Models\Program;
use App\Models\RegulatorySnapshot;
use App\Models\RentCalculation;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class AffordableRentLegalRegimeResolver
{
    public function resolveForDate(
        CarbonInterface $referenceDate,
        ?int $municipalityId = null,
    ): AffordableRentRegulatoryProfile {
        $date = $this->localDate($referenceDate);
        $regime = AffordableRentLegalRegime::forReferenceDate($date);
        $query = AffordableRentRegulatoryProfile::query()
            ->with('parentProfile')
            ->activeAt($date)
            ->where('legal_regime', $regime->value);

        if ($municipalityId !== null) {
            $municipalProfile = (clone $query)
                ->where('municipality_id', $municipalityId)
                ->latest('effective_from')
                ->latest('id')
                ->first();

            if ($municipalProfile instanceof AffordableRentRegulatoryProfile) {
                return $municipalProfile;
            }
        }

        $nationalProfile = $query
            ->whereNull('municipality_id')
            ->latest('effective_from')
            ->latest('id')
            ->first();

        if (! $nationalProfile instanceof AffordableRentRegulatoryProfile) {
            throw ValidationException::withMessages([
                'regulatory_profile_id' => "Não existe perfil regulamentar ativo para {$regime->label()} na data indicada.",
            ]);
        }

        return $nationalProfile;
    }

    public function assertProfileMatches(
        AffordableRentRegulatoryProfile $profile,
        CarbonInterface $referenceDate,
        ?int $municipalityId,
    ): void {
        $date = $this->localDate($referenceDate);
        $expectedRegime = AffordableRentLegalRegime::forReferenceDate($date);

        if ($profile->legal_regime !== $expectedRegime) {
            throw ValidationException::withMessages([
                'regulatory_profile_id' => 'O perfil regulamentar não corresponde ao regime aplicável na data de referência.',
            ]);
        }

        if (
            $profile->municipality_id !== null
            && $profile->municipality_id !== $municipalityId
        ) {
            throw ValidationException::withMessages([
                'regulatory_profile_id' => 'O perfil regulamentar pertence a outro Município.',
            ]);
        }

        if (
            $profile->effective_from->isAfter($date)
            || ($profile->effective_until !== null && $profile->effective_until->isBefore($date))
        ) {
            throw ValidationException::withMessages([
                'regulatory_profile_id' => 'O perfil regulamentar não está vigente na data de referência.',
            ]);
        }
    }

    public function profileForProgram(
        Program $program,
        CarbonInterface $referenceDate,
    ): AffordableRentRegulatoryProfile {
        $program->loadMissing(['municipality', 'regulatoryProfile', 'regulatorySnapshot.profile']);

        $profile = $this->profileFromSnapshot($program->getRelationValue('regulatorySnapshot'));

        if (! $profile instanceof AffordableRentRegulatoryProfile) {
            $candidate = $program->getRelationValue('regulatoryProfile');
            $profile = $candidate instanceof AffordableRentRegulatoryProfile ? $candidate : null;
        }

        if (! $profile instanceof AffordableRentRegulatoryProfile) {
            return $this->resolveForDate($referenceDate, $program->municipality_id);
        }

        return $profile;
    }

    public function profileForContest(
        Contest $contest,
        CarbonInterface $referenceDate,
    ): AffordableRentRegulatoryProfile {
        $contest->loadMissing([
            'program.municipality',
            'program.regulatoryProfile',
            'regulatoryProfile',
            'regulatorySnapshot.profile',
        ]);

        $profile = $this->profileFromSnapshot($contest->getRelationValue('regulatorySnapshot'));

        if (! $profile instanceof AffordableRentRegulatoryProfile) {
            $candidate = $contest->getRelationValue('regulatoryProfile');
            $profile = $candidate instanceof AffordableRentRegulatoryProfile ? $candidate : null;
        }

        $program = $contest->getRelationValue('program');

        if (! $profile instanceof AffordableRentRegulatoryProfile && $program instanceof Program) {
            $candidate = $program->getRelationValue('regulatoryProfile');
            $profile = $candidate instanceof AffordableRentRegulatoryProfile ? $candidate : null;
        }

        return $profile instanceof AffordableRentRegulatoryProfile
            ? $profile
            : $this->resolveForDate($referenceDate, $program instanceof Program ? $program->municipality_id : null);
    }

    public function profileForApplication(Application $application): ?AffordableRentRegulatoryProfile
    {
        $application->loadMissing([
            'regulatorySnapshot.profile',
            'contest.regulatorySnapshot.profile',
            'contest.regulatoryProfile',
            'program.regulatorySnapshot.profile',
            'program.regulatoryProfile',
        ]);

        $profile = $this->profileFromSnapshot($application->getRelationValue('regulatorySnapshot'));

        $contest = $application->getRelationValue('contest');
        if (! $profile instanceof AffordableRentRegulatoryProfile && $contest instanceof Contest) {
            $profile = $this->profileFromSnapshot($contest->getRelationValue('regulatorySnapshot'));

            if (! $profile instanceof AffordableRentRegulatoryProfile) {
                $candidate = $contest->getRelationValue('regulatoryProfile');
                $profile = $candidate instanceof AffordableRentRegulatoryProfile ? $candidate : null;
            }
        }

        $program = $application->getRelationValue('program');
        if (! $profile instanceof AffordableRentRegulatoryProfile && $program instanceof Program) {
            $profile = $this->profileFromSnapshot($program->getRelationValue('regulatorySnapshot'));

            if (! $profile instanceof AffordableRentRegulatoryProfile) {
                $candidate = $program->getRelationValue('regulatoryProfile');
                $profile = $candidate instanceof AffordableRentRegulatoryProfile ? $candidate : null;
            }
        }

        return $profile;
    }

    public function resolveContract(Contract $contract): LegalRegimeResolution
    {
        $contract->loadMissing([
            'regulatorySnapshot.profile',
            'rentCalculation.regulatorySnapshot.profile',
            'application.regulatorySnapshot.profile',
            'contest.regulatorySnapshot.profile',
            'program.regulatorySnapshot.profile',
        ]);

        $snapshot = $contract->getRelationValue('regulatorySnapshot');

        foreach (['rentCalculation', 'application', 'contest', 'program'] as $relation) {
            if ($snapshot instanceof RegulatorySnapshot) {
                break;
            }

            $related = $contract->getRelationValue($relation);
            if ($related instanceof RentCalculation || $related instanceof Application || $related instanceof Contest || $related instanceof Program) {
                $candidate = $related->getRelationValue('regulatorySnapshot');
                $snapshot = $candidate instanceof RegulatorySnapshot ? $candidate : null;
            }
        }

        if ($snapshot !== null) {
            return new LegalRegimeResolution(
                LegalRegimeResolutionStatus::Resolved,
                $snapshot->legal_regime,
                $snapshot->profile,
                CarbonImmutable::instance($snapshot->reference_date),
                'Regime recuperado de snapshot regulamentar bloqueado.',
            );
        }

        return new LegalRegimeResolution(
            LegalRegimeResolutionStatus::RequiresManualReview,
            null,
            null,
            null,
            'O contrato histórico não possui cadeia regulamentar inequívoca.',
        );
    }

    public function localDate(CarbonInterface $referenceDate): CarbonImmutable
    {
        return CarbonImmutable::instance($referenceDate)->setTimezone('Europe/Lisbon');
    }

    private function profileFromSnapshot(mixed $snapshot): ?AffordableRentRegulatoryProfile
    {
        if (! $snapshot instanceof RegulatorySnapshot) {
            return null;
        }

        $profile = $snapshot->getRelationValue('profile');

        return $profile instanceof AffordableRentRegulatoryProfile ? $profile : null;
    }
}
