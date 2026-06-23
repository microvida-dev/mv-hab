<?php

namespace App\Services\Simulator;

use App\Enums\ApplicationStatus;
use App\Enums\ImpedimentSeverity;
use App\Enums\ImpedimentType;
use App\Models\Contest;
use App\Models\User;

class SimulationImpedimentDetector
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array{score: float, missing_fields: list<string>, complete: bool}  $completeness
     * @return list<array<string, mixed>>
     */
    public function detect(array $input, array $completeness, ?Contest $contest = null, ?User $user = null): array
    {
        $impediments = [];

        foreach ($completeness['missing_fields'] as $field) {
            $impediments[] = $this->impediment(
                ImpedimentType::MissingHouseholdData,
                ImpedimentSeverity::Warning,
                'missing_'.$field,
                'Dados insuficientes',
                'Complete o campo obrigatório para melhorar a simulação.',
                'Preencha ou confirme este dado antes de iniciar uma candidatura.',
                false,
                $field,
            );
        }

        if (($input['has_property'] ?? false) === true) {
            $impediments[] = $this->blocking(
                ImpedimentType::ManualReviewRequired,
                'property_detected',
                'Possível impedimento por titularidade de imóvel',
                'Foi indicado que existe propriedade, usufruto ou posse de imóvel habitacional.',
                'Confirme a situação documental antes de submeter candidatura.',
                'has_property',
            );
        }

        if (($input['receives_housing_support'] ?? false) === true) {
            $impediments[] = $this->blocking(
                ImpedimentType::ManualReviewRequired,
                'housing_support_detected',
                'Possível acumulação de apoio habitacional',
                'Foi indicado benefício de apoio público habitacional ou habitação pública.',
                'Regularize ou esclareça a situação junto dos serviços municipais.',
                'receives_housing_support',
            );
        }

        if (($input['has_municipal_debt'] ?? false) === true) {
            $impediments[] = $this->blocking(
                ImpedimentType::ManualReviewRequired,
                'municipal_debt_detected',
                'Dívida municipal indicada',
                'Foi indicada dívida ao Município sem acordo de regularização.',
                'Confirme se existe acordo de regularização ativo.',
                'has_municipal_debt',
            );
        }

        if (($input['tax_regularized'] ?? null) === false || ($input['social_security_regularized'] ?? null) === false) {
            $impediments[] = $this->blocking(
                ImpedimentType::ManualReviewRequired,
                'tax_or_social_security_regularization',
                'Situação contributiva não regularizada',
                'A simulação indica situação não regularizada junto da AT ou Segurança Social.',
                'Regularize ou apresente comprovativo atualizado.',
                'tax_regularized',
            );
        }

        if (($input['false_declarations_history'] ?? false) === true || ($input['previous_municipal_eviction'] ?? false) === true) {
            $impediments[] = $this->blocking(
                ImpedimentType::ManualReviewRequired,
                'five_year_impediment',
                'Possível impedimento temporal',
                'Foram assinalados antecedentes que podem gerar impedimento por cinco anos.',
                'Solicite validação municipal antes de avançar.',
                'false_declarations_history',
            );
        }

        if ($contest instanceof Contest && ! $contest->isOpenForApplications()) {
            $type = $contest->opens_at?->isFuture() === true
                ? ImpedimentType::ContestNotYetOpen
                : ImpedimentType::ContestClosed;

            $impediments[] = $this->impediment(
                $type,
                ImpedimentSeverity::Warning,
                'contest_not_open',
                'Concurso fora do período de candidaturas',
                'O concurso selecionado não se encontra aberto para submissão.',
                'Consulte os prazos publicados antes de iniciar candidatura.',
                false,
                'contest_id',
            );
        }

        if ($user instanceof User && $contest instanceof Contest) {
            $hasActiveApplication = $user->applications()
                ->where('contest_id', $contest->id)
                ->whereIn('status', [
                    ApplicationStatus::Draft->value,
                    ApplicationStatus::Submitted->value,
                    ApplicationStatus::UnderReview->value,
                    ApplicationStatus::RequiresCorrection->value,
                    ApplicationStatus::CorrectionSubmitted->value,
                    ApplicationStatus::Eligible->value,
                ])
                ->exists();

            if ($hasActiveApplication) {
                $impediments[] = $this->impediment(
                    ImpedimentType::ExistingActiveApplication,
                    ImpedimentSeverity::Warning,
                    'existing_application',
                    'Candidatura existente',
                    'Já existe uma candidatura ativa ou rascunho para este concurso.',
                    'Reveja a candidatura existente antes de criar outra.',
                    false,
                    'contest_id',
                );
            }
        }

        return $impediments;
    }

    /**
     * @return array<string, mixed>
     */
    private function blocking(
        ImpedimentType $type,
        string $code,
        string $title,
        string $message,
        string $recommendation,
        string $field,
    ): array {
        return $this->impediment(
            $type,
            ImpedimentSeverity::Blocking,
            $code,
            $title,
            $message,
            $recommendation,
            true,
            $field,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function impediment(
        ImpedimentType $type,
        ImpedimentSeverity $severity,
        string $code,
        string $title,
        string $message,
        ?string $recommendation,
        bool $blocking,
        ?string $field,
    ): array {
        return [
            'type' => $type->value,
            'severity' => $severity->value,
            'code' => $code,
            'title' => $title,
            'message' => $message,
            'recommendation' => $recommendation,
            'is_blocking' => $blocking,
            'related_field' => $field,
        ];
    }
}
