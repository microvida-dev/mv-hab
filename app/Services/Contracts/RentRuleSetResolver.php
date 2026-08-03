<?php

namespace App\Services\Contracts;

use App\Enums\RentRuleSetStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Allocation;
use App\Models\Application;
use App\Models\RentRuleSet;
use App\Services\Regulatory\AffordableRentLegalRegimeResolver;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class RentRuleSetResolver
{
    public function __construct(
        private readonly AffordableRentLegalRegimeResolver $regimeResolver,
    ) {}

    public function resolve(
        Allocation $allocation,
        ?RentRuleSet $explicit = null,
        ?CarbonInterface $referenceDate = null,
    ): RentRuleSet {
        $referenceDate ??= now();
        $allocation->loadMissing('application');
        $application = $allocation->getRelationValue('application');
        $profile = $application instanceof Application
            ? $this->regimeResolver->profileForApplication($application)
            : null;

        if ($explicit !== null) {
            if ($explicit->status !== RentRuleSetStatus::Active) {
                throw ValidationException::withMessages(['rent_rule_set_id' => 'O conjunto de regras indicado não está ativo.']);
            }

            $isEffective = RentRuleSet::query()
                ->whereKey($explicit->getKey())
                ->activeAt($referenceDate)
                ->exists();

            if (! $isEffective) {
                throw ValidationException::withMessages(['rent_rule_set_id' => 'O conjunto de regras indicado não está vigente na data do cálculo.']);
            }

            $this->assertProfileMatches($explicit, $profile);

            return $explicit;
        }

        $regulatoryProfileId = $profile instanceof AffordableRentRegulatoryProfile
            ? $profile->id
            : null;

        $ruleSet = RentRuleSet::query()
            ->activeAt($referenceDate)
            ->when(
                $regulatoryProfileId !== null,
                fn ($query) => $query->where('regulatory_profile_id', $regulatoryProfileId),
            )
            ->where(function ($query) use ($allocation) {
                $query->where('contest_id', $allocation->contest_id)
                    ->orWhere(function ($builder) use ($allocation) {
                        $builder->whereNull('contest_id')
                            ->where('program_id', $allocation->program_id);
                    });
            })
            ->orderByRaw('case when contest_id is null then 1 else 0 end')
            ->latest('id')
            ->first();

        if (! $ruleSet) {
            throw ValidationException::withMessages(['rent_rule_set_id' => 'Não existe regra de renda ativa para o programa ou concurso.']);
        }

        return $ruleSet;
    }

    private function assertProfileMatches(
        RentRuleSet $ruleSet,
        ?AffordableRentRegulatoryProfile $profile,
    ): void {
        if (
            $profile instanceof AffordableRentRegulatoryProfile
            && $ruleSet->regulatory_profile_id !== $profile->id
        ) {
            throw ValidationException::withMessages([
                'rent_rule_set_id' => 'O conjunto de regras de renda não corresponde ao perfil regulamentar da candidatura.',
            ]);
        }
    }
}
