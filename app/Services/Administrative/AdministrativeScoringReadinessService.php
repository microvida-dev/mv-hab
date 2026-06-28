<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationStatus;
use App\Enums\EligibilityResult;
use App\Models\AdministrativeProcess;
use App\Models\Application;

class AdministrativeScoringReadinessService
{
    /**
     * @return array{ready: bool, items: list<array{label: string, passed: bool, detail: string}>}
     */
    public function forProcess(AdministrativeProcess $process): array
    {
        $application = $process->application;
        $processStatus = $this->processStatus($process);

        $items = [
            $this->item(
                'Processo admitido para classificação',
                $processStatus === AdministrativeProcessStatus::AdmittedForScoring,
                $processStatus === AdministrativeProcessStatus::AdmittedForScoring
                    ? 'Decisão administrativa aprovada.'
                    : 'A decisão de admissão ainda não foi aplicada ao processo.',
            ),
            $this->item(
                'Candidatura em estado compatível',
                $application instanceof Application && $this->hasCompatibleApplicationStatus($application),
                $application instanceof Application
                    ? 'Estado atual: '.($this->applicationStatus($application)?->label() ?? 'desconhecido').'.'
                    : 'Sem candidatura associada.',
            ),
            $this->item(
                'Elegibilidade técnica elegível',
                $application instanceof Application
                    && $application->latestEligibilityCheck?->result === EligibilityResult::Eligible,
                $this->eligibilityDetail($application),
            ),
            $this->item(
                'Concurso associado',
                $application instanceof Application && $application->getAttribute('contest_id') !== null,
                $application instanceof Application && $application->getAttribute('contest_id') !== null
                    ? 'Candidatura associada ao concurso selecionado.'
                    : 'A candidatura não tem concurso associado.',
            ),
        ];

        return [
            'ready' => collect($items)->every(fn (array $item): bool => $item['passed']),
            'items' => $items,
        ];
    }

    private function hasCompatibleApplicationStatus(Application $application): bool
    {
        return in_array($this->applicationStatus($application), [
            ApplicationStatus::Submitted,
            ApplicationStatus::UnderReview,
            ApplicationStatus::CorrectionSubmitted,
            ApplicationStatus::Eligible,
        ], true);
    }

    private function processStatus(AdministrativeProcess $process): ?AdministrativeProcessStatus
    {
        $status = $process->getAttribute('status');

        if ($status instanceof AdministrativeProcessStatus) {
            return $status;
        }

        return is_string($status) ? AdministrativeProcessStatus::tryFrom($status) : null;
    }

    private function applicationStatus(Application $application): ?ApplicationStatus
    {
        $status = $application->getAttribute('status');

        if ($status instanceof ApplicationStatus) {
            return $status;
        }

        return is_string($status) ? ApplicationStatus::tryFrom($status) : null;
    }

    private function eligibilityDetail(?Application $application): string
    {
        if (! $application instanceof Application) {
            return 'Sem candidatura associada.';
        }

        $result = $application->latestEligibilityCheck?->result;

        if ($result === EligibilityResult::Eligible) {
            return 'Última verificação: Elegível.';
        }

        if ($result instanceof EligibilityResult) {
            return 'Última verificação: '.$result->label().'. A candidatura não entra no snapshot enquanto não ficar Elegível.';
        }

        return 'Sem verificação de elegibilidade concluída.';
    }

    /**
     * @return array{label: string, passed: bool, detail: string}
     */
    private function item(string $label, bool $passed, string $detail): array
    {
        return [
            'label' => $label,
            'passed' => $passed,
            'detail' => $detail,
        ];
    }
}
