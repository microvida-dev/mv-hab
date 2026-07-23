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
                'administrative_processes.view',
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
                'documents.approve',
                'documents.reject',
                'documents.audit',
                'eligibility.view',
                'administrative_processes.view',
                'administrative_processes.update',
                'administrative_processes.audit',
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
                'exports.view',
                'exports.create',
                'exports.download',
                'exports.audit',
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
