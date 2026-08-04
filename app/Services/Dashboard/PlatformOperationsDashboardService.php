<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\AccessDenialReason;
use App\Enums\ActorProfile;
use App\Exceptions\AccessDeniedException;
use App\Models\User;

final class PlatformOperationsDashboardService
{
    public function __construct(
        private readonly DashboardAuthorizationService $authorization,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $profile = $this->authorization->actorProfile($user);

        if ($profile !== ActorProfile::PlatformAdministrator) {
            throw new AccessDeniedException(
                AccessDenialReason::MissingPermission,
                ['actor_profile' => $profile->value],
            );
        }

        $dashboard = [
            'greeting' => $this->greeting($user),
            'profile_label' => $profile->label(),
            'profile_keys' => [$profile->dashboardKey()],
            'team_names' => [],
            'workspaces' => [],
            'favorites' => [],
            'recent_items' => [],
            'workspace_intelligence' => [
                'preferred_key' => null,
                'preferred' => null,
                'workspaces' => [],
                'summary' => [
                    'workspaces' => 0,
                    'favorites' => 0,
                    'recent_items' => 0,
                    'preferred_label' => null,
                ],
            ],
            'adaptive_dashboard' => [
                'profile' => $profile->dashboardKey(),
                'profile_label' => $profile->label(),
                'eyebrow' => 'Administração da plataforma',
                'headline' => 'Visão global da plataforma',
                'description' => 'Acompanhe Municípios, segurança, acessos, onboarding e operação transversal do MV-HAB.',
                'icon' => 'security',
                'tone' => 'info',
                'risk_level' => 'unavailable',
                'risk_label' => 'Sem leitura de risco municipal',
                'primary_workspace_label' => null,
                'primary_action' => null,
                'focus_metrics' => [],
                'priority_widgets' => [],
                'summary' => [
                    'active_deadlines' => 0,
                    'available_actions' => 0,
                    'available_widgets' => 0,
                ],
            ],
            'priority_queue' => [
                'items' => [],
                'summary' => [
                    'count' => 0,
                    'critical' => 0,
                    'high' => 0,
                    'label' => 'Sem prioridades municipais carregadas',
                ],
            ],
            'search_groups' => [],
            'widgets' => [],
            'metrics' => [],
            'quick_actions' => [],
            'deadlines' => [],
            'notifications_summary' => [
                'label' => 'Notificações da plataforma',
                'description' => 'Os indicadores globais serão disponibilizados apenas através das fontes autorizadas da administração da plataforma.',
            ],
            'workspace_preferences' => [],
        ];

        return [
            'dashboard' => $dashboard,
            'productivity' => [],
            'workspaces' => [],
            'favorites' => [],
            'recentItems' => [],
            'quickActions' => [],
            'searchGroups' => [],
            'operationsSummary' => ['metrics' => []],
            'todayOperations' => [],
            'operationsTimeline' => [],
            'correctionOperations' => [],
        ];
    }

    private function greeting(User $user): string
    {
        $firstName = trim(explode(' ', trim((string) $user->name))[0]);
        $name = $firstName !== '' ? $firstName : 'utilizador';

        return 'Bom trabalho, '.$name;
    }
}
