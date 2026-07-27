<?php

namespace App\Services\Access;

use App\Models\Permission;
use DomainException;

class MunicipalRoleTemplateRegistry
{
    /**
     * @var array<string, array{label: string, description: string, permissions: list<string>}>
     */
    private const TEMPLATES = [
        'operador-recolha' => [
            'label' => 'Operador de recolha',
            'description' => 'Registo e atualização inicial de candidaturas e receção de documentos, sem poderes de decisão ou exportação.',
            'permissions' => [
                'dashboard.view',
                'applications.view',
                'applications.create',
                'applications.update',
                'documents.view',
                'documents.create',
                'documents.update',
                'documents.replace',
                'documents.download',
                'administrative_processes.view',
                'administrative_processes.create',
            ],
        ],
        'analista-candidaturas' => [
            'label' => 'Analista de candidaturas',
            'description' => 'Análise processual e documental de candidaturas, sem exportação, classificação ou acesso a áreas financeiras.',
            'permissions' => [
                'dashboard.view',
                'applications.view',
                'applications.update',
                'applications.audit',
                'documents.view',
                'documents.update',
                'documents.replace',
                'documents.download',
                'documents.analyze',
                'documents.review_ai',
                'documents.approve',
                'documents.reject',
                'documents.audit',
                'eligibility.view',
                'eligibility.run',
                'administrative_processes.view',
                'administrative_processes.update',
                'administrative_processes.assign',
                'administrative_processes.decide',
                'administrative_processes.complete',
                'administrative_processes.cancel',
                'administrative_processes.issue',
                'administrative_processes.mark_overdue',
                'administrative_processes.audit',
                'administrative_decisions.view',
                'administrative_decisions.create',
                'work_tasks.view',
                'work_tasks.claim',
                'work_tasks.update_status',
                'work_tasks.complete',
            ],
        ],
        'exportador-candidaturas' => [
            'label' => 'Exportador de candidaturas',
            'description' => 'Consulta e exportação controlada de candidaturas, incluindo descarga e auditoria das exportações.',
            'permissions' => [
                'dashboard.view',
                'applications.view',
                'applications.export',
                'reports.view',
                'reports.export',
                'reports.audit',
            ],
        ],
        'gestor-visitas' => [
            'label' => 'Gestor de visitas',
            'description' => 'Gestão operacional de disponibilidades, horários e visitas a habitações, sem acesso a candidaturas, documentos ou exportações.',
            'permissions' => [
                'dashboard.view',
                'visits.view',
                'visits.create',
                'visits.update',
                'visits.approve',
                'visits.reject',
                'visits.availabilities.view',
                'visits.availabilities.create',
                'visits.availabilities.update',
                'visits.availabilities.delete',
                'visits.availabilities.generate_slots',
                'visits.slots.view',
                'visits.slots.block',
                'visits.slots.unblock',
                'visits.confirm',
                'visits.complete',
                'visits.mark_no_show',
                'visits.cancel',
            ],
        ],
    ];

    /**
     * @return list<array{key: string, label: string, description: string, permissions: list<string>}>
     */
    public function all(): array
    {
        $templates = [];

        foreach (self::TEMPLATES as $key => $template) {
            $templates[] = [
                'key' => $key,
                'label' => $template['label'],
                'description' => $template['description'],
                'permissions' => $template['permissions'],
            ];
        }

        return $templates;
    }

    /**
     * @return array{key: string, label: string, description: string, permissions: list<string>, permission_ids: list<int>}
     */
    public function resolve(string $key): array
    {
        $template = self::TEMPLATES[$key] ?? null;

        if ($template === null) {
            throw new DomainException('O modelo de perfil municipal indicado não existe.');
        }

        $permissionNames = $template['permissions'];
        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->get(['id', 'name'])
            ->keyBy('name');
        $missing = collect($permissionNames)
            ->reject(fn (string $permission): bool => $permissions->has($permission))
            ->values()
            ->all();

        if ($missing !== []) {
            throw new DomainException(
                'O modelo não pode ser utilizado porque faltam permissões obrigatórias: '.implode(', ', $missing).'.'
            );
        }

        $permissionIds = [];
        foreach ($permissionNames as $name) {
            $permission = $permissions->get($name);

            if (! $permission instanceof Permission) {
                throw new DomainException('A permissão obrigatória deixou de estar disponível: '.$name.'.');
            }

            $permissionIds[] = (int) $permission->id;
        }

        return [
            'key' => $key,
            'label' => $template['label'],
            'description' => $template['description'],
            'permissions' => $permissionNames,
            'permission_ids' => $permissionIds,
        ];
    }
}
