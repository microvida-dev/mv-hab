<?php

namespace App\Services\Analytics;

use App\Models\User;

class ProfileAnalyticsService
{
    /**
     * @return array{label: string, scope: string, executive: bool, financial: bool, audit: bool}
     */
    public function resolve(User $user): array
    {
        $profile = 'Técnico Municipal';

        foreach ([
            'administrator' => 'Administrador',
            'jury' => 'Júri',
            'legal_manager' => 'Gestor Jurídico',
            'financial_manager' => 'Gestor Financeiro',
            'housing_manager' => 'Gestor de Habitação',
            'maintenance_manager' => 'Gestor de Manutenção',
            'inspection_manager' => 'Gestor de Vistorias',
            'support_agent' => 'Apoio Municipal',
            'auditor' => 'Auditor',
        ] as $role => $label) {
            if ($user->hasRole($role)) {
                $profile = $label;
                break;
            }
        }

        return [
            'label' => $profile,
            'scope' => $user->hasRole('auditor') ? 'Leitura e auditoria' : 'Operação municipal',
            'executive' => $user->hasPermission('reports.view_executive'),
            'financial' => $user->hasPermission('reports.view_financial') || $user->hasPermission('finance.view'),
            'audit' => $user->hasPermission('audit_logs.view') || $user->hasPermission('reports.audit'),
        ];
    }
}
