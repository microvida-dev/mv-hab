<?php

namespace App\Services\Regulatory;

use App\Enums\RegulatoryConfigurationStatus;
use App\Enums\RegulatoryProfileStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\AllocationRuleSet;
use App\Models\Contest;
use App\Models\EligibilityRuleSet;
use App\Models\Program;
use App\Models\RentRuleSet;
use App\Models\TypologyAdequacyRule;
use App\Services\Regulatory\RentLimits\RentLimitProviderRegistry;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class RegulatoryPublicationReadinessService
{
    public function __construct(
        private readonly AffordableRentLegalRegimeResolver $resolver,
        private readonly MunicipalRegulatoryOverlayService $overlayService,
        private readonly RentLimitProviderRegistry $rentLimitProviders,
    ) {}

    public function assertProgramReady(
        Program $program,
        CarbonInterface $referenceDate,
    ): AffordableRentRegulatoryProfile {
        $program->loadMissing(['municipality', 'regulatoryProfile.parentProfile']);
        $profile = $program->regulatoryProfile;

        if (! $profile instanceof AffordableRentRegulatoryProfile) {
            $this->fail('Selecione um perfil regulamentar antes de publicar o programa.');
        }

        $this->assertProfileReady($profile, $referenceDate, $program->municipality_id);

        if (blank($program->legal_basis)) {
            $this->fail('Preencha o enquadramento legal do programa antes da publicação.');
        }

        $this->assertRuleSets(
            $program->id,
            null,
            $profile,
            $referenceDate,
            'programa',
        );

        return $profile;
    }

    public function assertContestReady(
        Contest $contest,
        CarbonInterface $referenceDate,
    ): AffordableRentRegulatoryProfile {
        $contest->loadMissing([
            'program.municipality',
            'program.regulatoryProfile.parentProfile',
            'regulatoryProfile.parentProfile',
        ]);
        $profile = $contest->regulatoryProfile ?? $contest->program?->regulatoryProfile;

        if (! $profile instanceof AffordableRentRegulatoryProfile) {
            $this->fail('O concurso não possui perfil regulamentar configurado.');
        }

        $this->assertProfileReady($profile, $referenceDate, $contest->program?->municipality_id);
        $this->assertRuleSets(
            $contest->program_id,
            $contest->id,
            $profile,
            $referenceDate,
            'concurso',
        );

        return $profile;
    }

    private function assertProfileReady(
        AffordableRentRegulatoryProfile $profile,
        CarbonInterface $referenceDate,
        ?int $municipalityId,
    ): void {
        if ($profile->status !== RegulatoryProfileStatus::Active) {
            $this->fail('O perfil regulamentar deve estar ativo.');
        }

        $this->resolver->assertProfileMatches($profile, $referenceDate, $municipalityId);
        $this->overlayService->assertValid($profile);

        if ($profile->configuration_status !== RegulatoryConfigurationStatus::Complete) {
            $this->fail('A configuração regulamentar está incompleta e não permite publicação.');
        }

        foreach ([
            'eligibility_rules_configured' => 'regras de elegibilidade',
            'typology_rules_configured' => 'regras de tipologia',
            'contract_terms_configured' => 'prazos e termos contratuais',
        ] as $field => $label) {
            if (! $profile->{$field}) {
                $this->fail("A configuração de {$label} está incompleta.");
            }
        }

        $rentLimit = $this->rentLimitProviders
            ->forProfile($profile)
            ->limitsFor($profile, null, $referenceDate);

        if (! $rentLimit->isConfigured()) {
            $this->fail($rentLimit->message ?? 'A tabela de limites de renda está incompleta.');
        }
    }

    private function assertRuleSets(
        int $programId,
        ?int $contestId,
        AffordableRentRegulatoryProfile $profile,
        CarbonInterface $referenceDate,
        string $contextLabel,
    ): void {
        $context = function ($query) use ($programId, $contestId): void {
            if ($contestId === null) {
                $query
                    ->whereNull('contest_id')
                    ->where('program_id', $programId);

                return;
            }

            $query
                ->where('contest_id', $contestId)
                ->orWhere(fn ($fallback) => $fallback
                    ->whereNull('contest_id')
                    ->where('program_id', $programId));
        };

        $eligibility = EligibilityRuleSet::query()
            ->activeAt($referenceDate)
            ->where('regulatory_profile_id', $profile->id)
            ->where($context)
            ->exists();
        $rentRuleSet = RentRuleSet::query()
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
            ->exists();
        $allocation = AllocationRuleSet::query()
            ->active()
            ->where('regulatory_profile_id', $profile->id)
            ->where($context)
            ->exists();

        if (! $eligibility) {
            $this->fail("Configure um conjunto de regras de elegibilidade para o {$contextLabel}.");
        }

        if (! $typology) {
            $this->fail("Configure regras de adequação tipológica para o {$contextLabel}.");
        }

        if (! $allocation) {
            $this->fail("Configure regras de atribuição para o {$contextLabel}.");
        }

        $rentLimit = $this->rentLimitProviders
            ->forProfile($profile)
            ->limitsFor($profile, $rentRuleSet, $referenceDate);

        if (! $rentLimit->isConfigured() || ! $rentRuleSet instanceof RentRuleSet) {
            $this->fail($rentLimit->message ?? "Configure regras de renda para o {$contextLabel}.");
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['regulatory' => $message]);
    }
}
