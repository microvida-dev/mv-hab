<?php

namespace App\Services\Cases;

use App\Enums\ApplicationStatus;
use App\Models\Application;

class ProcessProgressService
{
    /**
     * @return array<int, array{key: string, label: string, status: string}>
     */
    public function forApplication(Application $application): array
    {
        $steps = [
            'received' => 'Recebida',
            'documents' => 'Documentação',
            'eligibility' => 'Elegibilidade',
            'scoring' => 'Pontuação',
            'provisional_list' => 'Lista Provisória',
            'hearing' => 'Audiência/Reclamações',
            'definitive_list' => 'Lista Definitiva',
            'allocation' => 'Atribuição',
            'contract' => 'Contrato',
            'tenant' => 'Inquilino',
        ];

        $current = $this->currentStep($application);

        return collect($steps)
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
                'status' => $this->stepStatus($key, $current, $application),
            ])
            ->values()
            ->all();
    }

    private function currentStep(Application $application): string
    {
        if ($application->leaseContracts()->exists()) {
            return 'contract';
        }

        if ($application->allocations()->exists()) {
            return 'allocation';
        }

        if ($application->definitiveListEntries()->exists()) {
            return 'definitive_list';
        }

        if ($application->complaints()->exists() || $application->hearings()->exists()) {
            return 'hearing';
        }

        if ($application->provisionalListEntries()->exists()) {
            return 'provisional_list';
        }

        if ($application->latestApplicationScore()->exists()) {
            return 'scoring';
        }

        if ($application->latestEligibilityCheck()->exists()) {
            return 'eligibility';
        }

        if ($application->documentSubmissions()->exists()) {
            return 'documents';
        }

        return $application->status === ApplicationStatus::Draft ? 'received' : 'documents';
    }

    private function stepStatus(string $key, string $current, Application $application): string
    {
        $order = ['received', 'documents', 'eligibility', 'scoring', 'provisional_list', 'hearing', 'definitive_list', 'allocation', 'contract', 'tenant'];
        $keyIndex = array_search($key, $order, true);
        $currentIndex = array_search($current, $order, true);

        if ($key === 'hearing' && ! $application->complaints()->exists() && ! $application->hearings()->exists() && $currentIndex > $keyIndex) {
            return 'skipped';
        }

        if ($keyIndex < $currentIndex) {
            return 'done';
        }

        if ($keyIndex === $currentIndex) {
            return $application->status === ApplicationStatus::RequiresCorrection ? 'warning' : 'current';
        }

        return 'pending';
    }
}
