<?php

namespace App\Services\CandidateExperience;

use App\Enums\ApplicationStatus;
use App\Enums\ContractStatus;
use App\Enums\EligibilityResult;
use App\Enums\SimulationResultStatus;
use App\Enums\SimulationSessionStatus;
use App\Enums\TenantPortalStatus;
use App\Models\User;

class CandidateNavigationService
{
    /**
     * @return array{
     *     home_route: string,
     *     groups: array<string, list<array{label: string, route: string, active: string, icon: string}>>,
     *     footer: list<array{label: string, route: string, active: string, icon: string}>
     * }
     */
    public function forUser(User $candidate): array
    {
        if ($this->hasTenantAccess($candidate)) {
            return [
                'home_route' => 'tenant.dashboard',
                'groups' => $this->tenantGroups(),
                'footer' => $this->footerLinks(),
            ];
        }

        $hasSimulation = $this->hasCompletedSimulation($candidate);
        $isEligible = $this->isEligible($candidate);
        $hasDraftApplication = $this->hasDraftApplication($candidate);
        $hasSubmittedApplication = $this->hasSubmittedApplication($candidate);
        $hasProvisionalListStage = $this->hasProvisionalListStage($candidate);
        $hasAllocation = $this->hasAllocation($candidate);

        $groups = [
            'Área pessoal' => [
                $this->link('Visão Geral', 'candidate.dashboard', 'candidate.dashboard', 'user-dashboard'),
                $this->link('O meu registo', 'candidate.registration.show', 'candidate.registration.*', 'user'),
                $this->link('Agregado', 'candidate.household.show', 'candidate.household*', 'household'),
                $this->link('Rendimentos', 'candidate.income-records.index', 'candidate.income-records.*', 'income'),
                $this->link('Habitação Atual', 'candidate.current-housing.show', 'candidate.current-housing.*', 'housing'),
            ],
            'Simulador' => [
                $this->link('Simulador', 'candidate.simulations.index', 'candidate.simulations.*', 'simulator'),
            ],
        ];

        if ($hasSimulation) {
            $groups['Simulador'][] = $this->link('Elegibilidade', 'candidate.eligibility.index', 'candidate.eligibility.*', 'check');
            $groups['Simulador'][] = $isEligible
                ? $this->link('Nova candidatura', 'public.contests.index', 'public.contests.*', 'contest')
                : $this->link('Consultar concursos', 'public.contests.index', 'public.contests.*', 'see-contest');
        } else {
            $groups['Oferta'] = [
                $this->link('Concursos', 'public.contests.index', 'public.contests.*', 'folder'),
            ];
        }

        if ($hasDraftApplication) {
            $groups['Candidatura'] = [
                $this->link('Candidaturas', 'candidate.applications.index', 'candidate.applications.*', 'user-application'),
                $this->link('Documentos', 'candidate.documents.index', 'candidate.documents.*', 'document'),
                $this->link('FAQ', 'candidate.contextual-faq.index', 'candidate.contextual-faq.*', 'faq'),
                $this->link('Apoio', 'candidate.support-tickets.index', 'candidate.support-tickets.*', 'user-message'),
            ];
        }

        if ($hasSubmittedApplication) {
            $groups['Processo'] = [
                $this->link('Estado da candidatura', 'candidate.applications.index', 'candidate.applications.*', 'status'),
                $this->link('Processo', 'candidate.processes.index', 'candidate.processes.*', 'process'),
                $this->link('Documentos', 'candidate.documents.index', 'candidate.documents.*', 'document'),
                $this->link('Interações', 'candidate.interactions.index', 'candidate.interactions.*', 'interactions'),
                $this->link('Aperfeiçoamentos', 'candidate.correction-requests.index', 'candidate.correction-requests.*', 'edit'),
                $this->link('Visitas', 'candidate.visits.index', 'candidate.visits.*', 'user-inspection'),
                $this->link('FAQ', 'candidate.contextual-faq.index', 'candidate.contextual-faq.*', 'faq'),
                $this->link('Apoio', 'candidate.support-tickets.index', 'candidate.support-tickets.*', 'ticket'),
            ];
        }

        if ($hasProvisionalListStage) {
            $groups['Lista provisória'] = [
                $this->link('Audiência Prévia', 'candidate.hearings.index', 'candidate.hearings.*', 'audit'),
                $this->link('Reclamações', 'candidate.complaints.index', 'candidate.complaints.*', 'complaints'),
            ];
        }

        if ($hasAllocation) {
            $groups['Atribuição'] = [
                $this->link('Ofertas', 'candidate.allocation-offers.index', 'candidate.allocation-offers.*', 'offers'),
                $this->link('Atribuições', 'candidate.allocations.index', 'candidate.allocations.*', 'atribuition'),
            ];
        }

        return [
            'home_route' => 'candidate.dashboard',
            'groups' => $groups,
            'footer' => $this->footerLinks(),
        ];
    }

    /**
     * @return array<string, list<array{label: string, route: string, active: string, icon: string}>>
     */
    private function tenantGroups(): array
    {
        return [
            'Área do inquilino' => [
                $this->link('Área do Inquilino', 'tenant.dashboard', 'tenant.dashboard', 'home'),
                $this->link('Contratos', 'tenant.contracts.index', 'tenant.contracts.*', 'document'),
                $this->link('Pagamentos', 'tenant.payments.index', 'tenant.payments.*', 'payment'),
                $this->link('Vistorias', 'tenant.inspections.index', 'tenant.inspections.*', 'check'),
                $this->link('Manutenção', 'tenant.maintenance.index', 'tenant.maintenance.*', 'maintenance'),
                $this->link('Comunicações', 'tenant.communications.index', 'tenant.communications.*', 'document'),
            ],
        ];
    }

    /**
     * @return list<array{label: string, route: string, active: string, icon: string}>
     */
    private function footerLinks(): array
    {
        return [
            $this->link('Ir para Portal Público', 'public.portal', 'public.*', 'home'),
            $this->link('Notificações', 'candidate.notifications.index', 'candidate.notifications.*', 'bell'),
        ];
    }

    /**
     * @return array{label: string, route: string, active: string, icon: string}
     */
    private function link(string $label, string $route, string $active, string $icon): array
    {
        return compact('label', 'route', 'active', 'icon');
    }

    private function hasCompletedSimulation(User $candidate): bool
    {
        return $candidate->simulationSessions()
            ->whereIn('status', [
                SimulationSessionStatus::Completed->value,
                SimulationSessionStatus::Saved->value,
                SimulationSessionStatus::ConvertedToRegistration->value,
                SimulationSessionStatus::ConvertedToApplicationDraft->value,
            ])
            ->exists()
            || $candidate->eligibilityChecks()->exists();
    }

    private function isEligible(User $candidate): bool
    {
        return $candidate->eligibilityChecks()
            ->where('result', EligibilityResult::Eligible->value)
            ->exists()
            || $candidate->simulationSessions()
                ->where('result_status', SimulationResultStatus::LikelyEligible->value)
                ->exists()
            || $candidate->simulationSessions()
                ->whereHas('result', fn ($query) => $query->where('result_status', SimulationResultStatus::LikelyEligible->value))
                ->exists();
    }

    private function hasDraftApplication(User $candidate): bool
    {
        return $candidate->applications()
            ->where('status', ApplicationStatus::Draft->value)
            ->exists();
    }

    private function hasSubmittedApplication(User $candidate): bool
    {
        return $candidate->applications()
            ->where(function ($query): void {
                $query->whereNotNull('submitted_at')
                    ->orWhereIn('status', [
                        ApplicationStatus::Submitted->value,
                        ApplicationStatus::UnderReview->value,
                        ApplicationStatus::RequiresCorrection->value,
                        ApplicationStatus::CorrectionSubmitted->value,
                        ApplicationStatus::Eligible->value,
                        ApplicationStatus::Ineligible->value,
                        ApplicationStatus::Excluded->value,
                    ]);
            })
            ->exists();
    }

    private function hasProvisionalListStage(User $candidate): bool
    {
        return $candidate->applications()
            ->whereHas('provisionalListEntries')
            ->exists()
            || $candidate->hearings()
                ->where('candidate_visible', true)
                ->exists()
            || $candidate->complaints()
                ->where('candidate_visible', true)
                ->exists();
    }

    private function hasAllocation(User $candidate): bool
    {
        return $candidate->allocationOffers()->exists()
            || $candidate->allocations()->exists()
            || $candidate->applications()->whereHas('allocationOffers')->exists()
            || $candidate->applications()->whereHas('allocations')->exists();
    }

    private function hasTenantAccess(User $candidate): bool
    {
        return $candidate->tenantProfile()
            ->where('status', TenantPortalStatus::Active->value)
            ->exists()
            || $candidate->leaseContracts()
                ->whereIn('status', [
                    ContractStatus::Signed->value,
                    ContractStatus::Active->value,
                    ContractStatus::Renewed->value,
                ])
                ->exists();
    }
}
