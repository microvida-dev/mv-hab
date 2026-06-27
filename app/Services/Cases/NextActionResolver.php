<?php

namespace App\Services\Cases;

use App\Enums\DocumentStatus;
use App\Models\Application;
use App\Models\User;

class NextActionResolver
{
    public function __construct(private readonly CaseAuthorizationService $authorization) {}

    /**
     * @return array<string, mixed>
     */
    public function forApplication(User $user, Application $application): array
    {
        if ($user->hasRole('auditor')) {
            return [
                'label' => 'Acompanhar processo',
                'description' => 'Perfil de auditoria com consulta controlada e sem ações mutáveis.',
                'route' => null,
                'enabled' => false,
            ];
        }

        $pendingDocuments = $application->documentSubmissions()
            ->whereIn('status', [
                DocumentStatus::Missing->value,
                DocumentStatus::Submitted->value,
                DocumentStatus::UnderReview->value,
                DocumentStatus::Rejected->value,
                DocumentStatus::Expired->value,
            ])
            ->exists();

        if ($pendingDocuments) {
            return $this->action(
                $user,
                'Validar documentos',
                'Existem documentos em falta, submetidos ou a rever.',
                'admin.document-reviews.index',
                'documents.view',
            );
        }

        if (! $application->latestEligibilityCheck()->exists()) {
            return $this->action(
                $user,
                'Executar elegibilidade',
                'A candidatura ainda não tem verificação formal de elegibilidade.',
                'backoffice.eligibility.rule-sets.index',
                'eligibility.view',
            );
        }

        if (! $application->latestApplicationScore()->exists()) {
            return $this->action(
                $user,
                'Rever pontuação',
                'A candidatura ainda não tem classificação operacional registada.',
                'backoffice.scoring.application-scores.index',
                'scoring.view',
            );
        }

        if (! $application->provisionalListEntries()->exists()) {
            return $this->action(
                $user,
                'Validar para lista provisória',
                'A candidatura ainda não consta de uma lista provisória.',
                'backoffice.lists.provisional.index',
                'public_lists.view',
            );
        }

        if ($application->complaints()->whereIn('status', ['submitted', 'pending', 'under_review', 'open'])->exists()) {
            return $this->action(
                $user,
                'Analisar reclamação',
                'Existe reclamação ou audiência pendente associada ao processo.',
                'backoffice.complaints.index',
                'complaints.view',
            );
        }

        if (! $application->definitiveListEntries()->exists()) {
            return $this->action(
                $user,
                'Validar lista definitiva',
                'A candidatura ainda não consta de lista definitiva.',
                'backoffice.lists.definitive.index',
                'public_lists.view',
            );
        }

        if (! $application->leaseContracts()->exists()) {
            return $this->action(
                $user,
                'Preparar contrato',
                'A candidatura está pronta para transição administrativa quando aplicável.',
                'backoffice.contracts.leases.index',
                'contracts.view',
            );
        }

        return [
            'label' => 'Acompanhar processo',
            'description' => 'O processo não tem ação operacional prioritária neste momento.',
            'route' => null,
            'enabled' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function action(User $user, string $label, string $description, string $route, string $permission): array
    {
        $enabled = $this->authorization->canSeeItem($user, [
            'route' => $route,
            'permission' => $permission,
        ]);

        return [
            'label' => $label,
            'description' => $description,
            'route' => $enabled ? $route : null,
            'enabled' => $enabled,
        ];
    }
}
