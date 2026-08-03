<?php

namespace App\Services\Access;

use App\Models\Permission;
use App\Services\Support\CanonicalJsonHasher;
use DomainException;

/**
 * @phpstan-type MunicipalTemplate array{
 *     key: string,
 *     version: string,
 *     label: string,
 *     description: string,
 *     permissions: list<string>,
 *     capabilities: list<string>,
 *     excluded_permissions: list<string>,
 *     entitlement_dependencies: list<string>,
 *     segregation_class: string,
 *     fingerprint: string
 * }
 * @phpstan-type ResolvedMunicipalTemplate array{
 *     key: string,
 *     version: string,
 *     label: string,
 *     description: string,
 *     permissions: list<string>,
 *     capabilities: list<string>,
 *     excluded_permissions: list<string>,
 *     entitlement_dependencies: list<string>,
 *     segregation_class: string,
 *     fingerprint: string,
 *     permission_ids: list<int>
 * }
 */
class MunicipalRoleTemplateRegistry
{
    /**
     * @var array<string, array{
     *     version: string,
     *     label: string,
     *     description: string,
     *     permissions: list<string>,
     *     capabilities: list<string>,
     *     excluded_permissions: list<string>,
     *     entitlement_dependencies: list<string>,
     *     segregation_class: string
     * }>
     */
    private const TEMPLATES = [
        'operador-recolha' => [
            'version' => '1.0.0',
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
            'capabilities' => [
                'Registar e atualizar a receção inicial de candidaturas',
                'Receber, consultar, substituir e descarregar documentos',
            ],
            'excluded_permissions' => [
                'documents.approve',
                'documents.reject',
                'applications.export',
                'reports.export_sensitive',
            ],
            'entitlement_dependencies' => ['applications.intake'],
            'segregation_class' => 'program53_mutable',
        ],
        'analista-candidaturas' => [
            'version' => '1.0.0',
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
            'capabilities' => [
                'Rever e decidir documentos',
                'Executar elegibilidade e gerir processos administrativos',
                'Gerir tarefas de análise',
            ],
            'excluded_permissions' => [
                'applications.export',
                'reports.export',
                'reports.export_sensitive',
            ],
            'entitlement_dependencies' => ['applications.review'],
            'segregation_class' => 'program53_mutable',
        ],
        'exportador-candidaturas' => [
            'version' => '1.0.0',
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
            'capabilities' => [
                'Consultar candidaturas',
                'Gerar e descarregar exportações municipais não sensíveis',
                'Consultar a auditoria das exportações',
            ],
            'excluded_permissions' => [
                'documents.approve',
                'documents.reject',
                'reports.export_sensitive',
            ],
            'entitlement_dependencies' => ['applications.export'],
            'segregation_class' => 'program53_export',
        ],
        'gestor-visitas' => [
            'version' => '1.0.0',
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
            'capabilities' => [
                'Gerir disponibilidades e horários de visita',
                'Confirmar, concluir ou cancelar visitas',
            ],
            'excluded_permissions' => [
                'applications.view',
                'documents.view',
                'applications.export',
                'reports.export_sensitive',
            ],
            'entitlement_dependencies' => [],
            'segregation_class' => 'municipal_operations_mutable',
        ],
        'analista-candidaturas-exportacao' => [
            'version' => '1.0.0',
            'label' => 'Analista de candidaturas e exportação',
            'description' => 'Análise processual e documental em bloco, gestão de aperfeiçoamentos, selagem e publicação de lotes e exportação municipal não sensível, sem administração de acessos, finanças ou exportação sensível.',
            'permissions' => [
                'dashboard.view',
                'applications.view',
                'applications.update',
                'applications.audit',
                'applications.export',
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
                'administrative_processes.create',
                'administrative_processes.update',
                'administrative_processes.assign',
                'administrative_processes.decide',
                'administrative_processes.complete',
                'administrative_processes.cancel',
                'administrative_processes.issue',
                'administrative_processes.mark_overdue',
                'administrative_processes.publish',
                'administrative_processes.audit',
                'administrative_decisions.view',
                'administrative_decisions.create',
                'work_tasks.view',
                'work_tasks.claim',
                'work_tasks.update_status',
                'work_tasks.complete',
                'reports.view',
                'reports.export',
                'reports.audit',
            ],
            'capabilities' => [
                'Analisar candidaturas e decidir documentos',
                'Gerir aperfeiçoamentos e segunda análise',
                'Criar, selar e publicar lotes de revisão',
                'Gerar e descarregar exportações municipais não sensíveis',
                'Consultar auditoria operacional e de exportações',
            ],
            'excluded_permissions' => [
                'reports.export_sensitive',
                'roles.*',
                'users.*',
                'teams.*',
                'platform_operators.*',
                'finance.*',
                'contracts.*',
                'payments.*',
                'privacy.*',
                'rgpd.*',
            ],
            'entitlement_dependencies' => [
                'applications.review',
                'applications.export',
            ],
            'segregation_class' => 'program53_mutable',
        ],
        'tecnico-operacoes-concurso' => [
            'version' => '1.0.0',
            'label' => 'Técnico de Operações do Concurso',
            'description' => 'Receção, análise e validação em lote de candidaturas, revisão documental, resultados provisórios, audiência prévia, visitas e exportação integral do concurso.',
            'permissions' => [
                'dashboard.view',
                'security.manage_own_mfa',
                'agenda.view',
                'work_tasks.view',
                'work_tasks.view_team',
                'work_tasks.claim',
                'work_tasks.update_status',
                'work_tasks.complete',
                'work_tasks.audit',
                'work_tasks.dashboard',
                'programs.view',
                'contests.view',
                'housing_units.view',
                'citizens.view',
                'citizens.create',
                'citizens.update',
                'citizens.export',
                'adhesion_registrations.view',
                'adhesion_registrations.create',
                'adhesion_registrations.update',
                'adhesion_registrations.approve',
                'adhesion_registrations.reject',
                'adhesion_registrations.export',
                'applications.view',
                'applications.create',
                'applications.update',
                'applications.audit',
                'applications.export',
                'households.view',
                'households.create',
                'households.update',
                'households.export',
                'income_records.view',
                'income_records.create',
                'income_records.update',
                'income_records.export',
                'documents.view',
                'documents.create',
                'documents.update',
                'documents.replace',
                'documents.download',
                'documents.preview',
                'documents.analyze',
                'documents.review_ai',
                'documents.approve',
                'documents.reject',
                'documents.export',
                'documents.audit',
                'eligibility.view',
                'eligibility.run',
                'eligibility.export',
                'administrative_processes.view',
                'administrative_processes.create',
                'administrative_processes.update',
                'administrative_processes.assign',
                'administrative_processes.decide',
                'administrative_processes.complete',
                'administrative_processes.cancel',
                'administrative_processes.issue',
                'administrative_processes.mark_overdue',
                'administrative_processes.publish',
                'administrative_processes.export',
                'administrative_processes.audit',
                'administrative_decisions.view',
                'administrative_decisions.create',
                'public_lists.view',
                'public_lists.create',
                'public_lists.update',
                'public_lists.generate',
                'public_lists.review',
                'public_lists.approve',
                'public_lists.reject',
                'public_lists.publish',
                'public_lists.lock',
                'public_lists.open_complaint_period',
                'public_lists.close_complaint_period',
                'public_lists.export',
                'public_lists.audit',
                'hearings.view',
                'hearings.create',
                'hearings.issue',
                'hearings.review',
                'hearings.accept',
                'hearings.reject',
                'hearings.close',
                'hearings.cancel',
                'notifications.view',
                'notifications.create',
                'notifications.receipts.download',
                'communications.view',
                'communications.create',
                'notification_templates.view',
                'notification_template_versions.view',
                'notification_event_rules.view',
                'notification_preferences.view',
                'visits.view',
                'visits.create',
                'visits.update',
                'visits.approve',
                'visits.reject',
                'visits.export',
                'visits.confirm',
                'visits.complete',
                'visits.cancel',
                'visits.mark_no_show',
                'visits.availabilities.view',
                'visits.availabilities.create',
                'visits.availabilities.update',
                'visits.availabilities.delete',
                'visits.availabilities.generate_slots',
                'visits.slots.view',
                'visits.slots.block',
                'visits.slots.unblock',
                'reports.view',
                'reports.run',
                'reports.export',
                'reports.audit',
                'reports.view_sensitive',
                'reports.export_sensitive',
                'reports.export_nominal',
            ],
            'capabilities' => [
                'Receber e atualizar candidaturas',
                'Validar candidaturas e documentos em lote',
                'Gerir pedidos de aperfeiçoamento',
                'Preparar e publicar resultados provisórios',
                'Tratar audiências prévias',
                'Gerir casas abertas, horários e visitas',
                'Recolher e exportar dados das visitas',
                'Exportar dados normais, nominais e sensíveis do concurso',
            ],
            'excluded_permissions' => [
                'users.*',
                'roles.*',
                'teams.*',
                'permission_reviews.*',
                'municipalities.*',
                'municipality_features.*',
                'platform_operators.*',
                'security.update',
                'security.resolve',
                'security.approve',
                'security.revoke_sessions',
                'settings.*',
                'scoring.*',
                'complaints.*',
                'allocations.*',
                'lotteries.*',
                'contracts.*',
                'payments.*',
                'finance.*',
                'maintenance_requests.*',
                'maintenance.*',
                'inspections.*',
                'privacy.*',
                'rgpd.*',
                'reports.export_financial',
                'exports.rgpd',
            ],
            'entitlement_dependencies' => [
                'applications.intake',
                'applications.review',
                'applications.export',
            ],
            'segregation_class' => 'program53_mutable',
        ],
    ];

    public function __construct(private readonly CanonicalJsonHasher $hasher) {}

    /** @return list<MunicipalTemplate> */
    public function all(): array
    {
        return array_map(
            fn (string $key): array => $this->definition($key),
            $this->keys(),
        );
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys(self::TEMPLATES);
    }

    /** @return ResolvedMunicipalTemplate */
    public function resolve(string $key): array
    {
        $template = $this->definition($key);
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

        return [...$template, 'permission_ids' => $permissionIds];
    }

    public function isProgram53Mutable(string $key): bool
    {
        return $this->definition($key)['segregation_class'] === 'program53_mutable';
    }

    /** @return MunicipalTemplate */
    private function definition(string $key): array
    {
        $template = self::TEMPLATES[$key] ?? null;

        if ($template === null) {
            throw new DomainException('O modelo de perfil municipal indicado não existe.');
        }

        $permissions = $template['permissions'];
        sort($permissions, SORT_STRING);

        return [
            'key' => $key,
            'version' => $template['version'],
            'label' => $template['label'],
            'description' => $template['description'],
            'permissions' => $template['permissions'],
            'capabilities' => $template['capabilities'],
            'excluded_permissions' => $template['excluded_permissions'],
            'entitlement_dependencies' => $template['entitlement_dependencies'],
            'segregation_class' => $template['segregation_class'],
            'fingerprint' => $this->hasher->hash([
                'version' => $template['version'],
                'permissions' => $permissions,
            ]),
        ];
    }
}
