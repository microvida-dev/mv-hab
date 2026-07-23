<?php

namespace App\Services\Access;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PermissionCatalogService
{
    /** @var array<string, string> */
    private const DOMAIN_LABELS = [
        'applications' => 'Candidaturas',
        'documents' => 'Documentos',
        'eligibility' => 'Elegibilidade',
        'scoring' => 'Classificação',
        'hearings' => 'Audiência',
        'lists' => 'Listas',
        'contracts' => 'Contratos',
        'finance' => 'Finanças',
        'maintenance' => 'Manutenção',
        'reports' => 'Relatórios',
        'administration' => 'Administração',
        'rgpd' => 'RGPD',
        'other' => 'Outros',
    ];

    /** @var list<string> */
    private const DOMAIN_ORDER = [
        'applications',
        'documents',
        'eligibility',
        'scoring',
        'hearings',
        'lists',
        'contracts',
        'finance',
        'maintenance',
        'reports',
        'administration',
        'rgpd',
        'other',
    ];

    /** @var array<string, string> */
    private const MODULE_DOMAINS = [
        'adhesion_registrations' => 'applications',
        'administrative_processes' => 'applications',
        'applications' => 'applications',
        'candidate_experience' => 'applications',
        'citizens' => 'applications',
        'households' => 'applications',
        'income_records' => 'applications',
        'documents' => 'documents',
        'eligibility' => 'eligibility',
        'simulator' => 'eligibility',
        'scoring' => 'scoring',
        'complaints' => 'hearings',
        'public_lists' => 'lists',
        'allocations' => 'lists',
        'contracts' => 'contracts',
        'finance' => 'finance',
        'payments' => 'finance',
        'housing_units' => 'maintenance',
        'inspections' => 'maintenance',
        'maintenance_requests' => 'maintenance',
        'visits' => 'maintenance',
        'dashboard' => 'reports',
        'exports' => 'reports',
        'reports' => 'reports',
        'access_audit' => 'rgpd',
        'audit_logs' => 'rgpd',
        'privacy' => 'rgpd',
        'rgpd' => 'rgpd',
        'security' => 'rgpd',
        'contextual_faqs' => 'administration',
        'contests' => 'administration',
        'municipalities' => 'administration',
        'municipality_features' => 'administration',
        'notifications' => 'administration',
        'programs' => 'administration',
        'roles' => 'administration',
        'settings' => 'administration',
        'support' => 'administration',
        'teams' => 'administration',
        'users' => 'administration',
        'work_tasks' => 'administration',
    ];

    /** @var array<string, string> */
    private const MODULE_LABELS = [
        'access_audit' => 'auditoria de acessos',
        'adhesion_registrations' => 'registos de adesão',
        'administrative_processes' => 'processos administrativos',
        'allocations' => 'atribuições',
        'applications' => 'candidaturas',
        'audit_logs' => 'registos de auditoria',
        'candidate_experience' => 'experiência do candidato',
        'citizens' => 'munícipes',
        'complaints' => 'reclamações',
        'contests' => 'concursos',
        'contextual_faqs' => 'perguntas frequentes',
        'contracts' => 'contratos',
        'dashboard' => 'Painel Principal',
        'documents' => 'documentos',
        'eligibility' => 'elegibilidade',
        'exports' => 'exportações',
        'finance' => 'gestão financeira',
        'households' => 'agregados',
        'housing_units' => 'fogos',
        'income_records' => 'rendimentos',
        'inspections' => 'vistorias',
        'maintenance_requests' => 'pedidos de manutenção',
        'municipalities' => 'municípios',
        'municipality_features' => 'funcionalidades municipais',
        'notifications' => 'notificações',
        'payments' => 'pagamentos',
        'privacy' => 'privacidade',
        'programs' => 'programas',
        'public_lists' => 'listas públicas',
        'reports' => 'relatórios',
        'rgpd' => 'operações RGPD',
        'roles' => 'perfis de acesso',
        'scoring' => 'classificação',
        'security' => 'segurança',
        'settings' => 'configurações',
        'simulator' => 'simulador',
        'support' => 'apoio',
        'teams' => 'equipas',
        'users' => 'utilizadores',
        'visits' => 'visitas',
        'work_tasks' => 'tarefas de trabalho',
    ];

    /** @var array<string, string> */
    private const ACTION_LABELS = [
        'view' => 'Consultar',
        'create' => 'Criar',
        'update' => 'Alterar',
        'delete' => 'Eliminar',
        'approve' => 'Aprovar',
        'reject' => 'Rejeitar',
        'export' => 'Exportar',
        'audit' => 'Consultar auditoria de',
        'publish' => 'Publicar',
        'assign' => 'Atribuir',
        'remove' => 'Remover',
        'download' => 'Descarregar',
        'claim' => 'Assumir',
        'reassign' => 'Reatribuir',
        'complete' => 'Concluir',
        'cancel' => 'Cancelar',
        'dashboard' => 'Consultar painel de',
        'manage' => 'Gerir',
        'manage_members' => 'Gerir membros de',
        'manage_sla' => 'Gerir SLA de',
        'update_status' => 'Alterar estado de',
        'view_team' => 'Consultar tarefas da equipa em',
        'deactivate' => 'Desativar',
        'reactivate' => 'Reativar',
        'force_mfa' => 'Impor MFA a',
        'reset_password' => 'Repor palavra-passe de',
        'view_access_logs' => 'Consultar registos de acesso',
        'revoke_sessions' => 'Revogar sessões',
        'audit_sensitive_access' => 'Auditar acessos sensíveis',
        'view_executive' => 'Consultar indicadores executivos',
        'view_sensitive' => 'Consultar indicadores sensíveis',
        'view_financial' => 'Consultar indicadores financeiros',
        'view_maintenance' => 'Consultar indicadores de manutenção',
        'export_sensitive' => 'Exportar dados sensíveis',
        'export_financial' => 'Exportar dados financeiros',
        'export_nominal' => 'Exportar dados nominais',
        'retention.view' => 'Consultar políticas de retenção',
        'retention.manage' => 'Gerir políticas de retenção',
        'anonymization.request' => 'Pedir anonimização',
        'anonymization.approve' => 'Aprovar anonimização',
        'anonymization.execute' => 'Executar anonimização',
        'dpo.approve' => 'Aprovar como EPD',
        'sensitive.create' => 'Criar exportação sensível',
        'sensitive.download' => 'Descarregar exportação sensível',
        'sensitive.audit' => 'Auditar exportação sensível',
        'rgpd' => 'Executar exportação RGPD',
    ];

    /** @var array<string, string> */
    private const PERMISSION_LABEL_OVERRIDES = [
        'applications.view' => 'Consultar candidaturas',
        'applications.create' => 'Registar candidaturas',
        'applications.update' => 'Alterar candidaturas',
        'applications.export' => 'Exportar candidaturas',
        'documents.view' => 'Consultar documentos',
        'documents.create' => 'Receber documentos',
        'documents.approve' => 'Validar documentos',
        'documents.reject' => 'Rejeitar documentos',
        'eligibility.view' => 'Consultar elegibilidade',
        'roles.view' => 'Consultar perfis de acesso',
        'roles.create' => 'Criar perfis de acesso',
        'roles.update' => 'Alterar perfis de acesso',
        'roles.delete' => 'Eliminar perfis de acesso',
        'roles.assign' => 'Atribuir perfis a utilizadores',
        'roles.remove' => 'Remover perfis de utilizadores',
        'roles.audit' => 'Consultar auditoria de perfis',
        'municipality_features.view' => 'Consultar funcionalidades municipais',
        'municipality_features.update' => 'Alterar funcionalidades municipais',
        'municipality_features.audit' => 'Consultar auditoria de funcionalidades municipais',
    ];

    /** @var list<string> */
    private const SENSITIVE_MODULES = [
        'access_audit',
        'audit_logs',
        'contracts',
        'exports',
        'finance',
        'payments',
        'privacy',
        'rgpd',
        'roles',
        'security',
        'users',
    ];

    /** @var list<string> */
    private const SENSITIVE_ACTIONS = [
        'approve',
        'reject',
        'delete',
        'export',
        'audit',
        'publish',
        'assign',
        'remove',
        'manage',
        'manage_members',
        'manage_sla',
        'force_mfa',
        'reset_password',
        'deactivate',
        'reactivate',
        'reassign',
        'revoke_sessions',
        'audit_sensitive_access',
        'download',
    ];

    /** @var list<string> */
    private const SENSITIVE_PERMISSION_PREFIXES = [
        'administrative_processes.approve',
        'administrative_processes.reject',
        'administrative_processes.update',
        'allocations.approve',
        'allocations.reject',
        'documents.approve',
        'documents.reject',
        'eligibility.approve',
        'eligibility.reject',
        'eligibility.update',
        'public_lists.approve',
        'public_lists.publish',
        'public_lists.reject',
        'scoring.approve',
        'scoring.create',
        'scoring.reject',
        'scoring.update',
        'municipality_features.update',
        'municipality_features.audit',
    ];

    /**
     * @param  Collection<int, Permission>|null  $permissions
     * @return list<array{key: string, label: string, permissions: list<array{id: int, name: string, module: string, action: string, label: string, action_label: string, sensitive: bool}>}>
     */
    public function grouped(?Collection $permissions = null): array
    {
        $permissions ??= Permission::query()
            ->where('name', '!=', '*')
            ->orderBy('module')
            ->orderBy('action')
            ->get();

        $grouped = $permissions
            ->reject(fn (Permission $permission): bool => $permission->name === '*')
            ->map(function (Permission $permission): array {
                $metadata = $this->metadata($permission->name, $permission->module, $permission->action);

                return [
                    'id' => (int) $permission->id,
                    'name' => $permission->name,
                    'module' => $permission->module,
                    'action' => $permission->action,
                    'label' => $metadata['label'],
                    'action_label' => $metadata['action_label'],
                    'sensitive' => $metadata['sensitive'],
                    'domain' => $metadata['domain'],
                ];
            })
            ->groupBy('domain');

        return array_values(collect(self::DOMAIN_ORDER)
            ->filter(fn (string $domain): bool => $grouped->has($domain))
            ->map(fn (string $domain): array => [
                'key' => $domain,
                'label' => self::DOMAIN_LABELS[$domain],
                'permissions' => array_values($grouped->get($domain, collect())
                    ->sortBy(fn (array $permission): string => $permission['label']."\0".$permission['name'])
                    ->map(fn (array $permission): array => [
                        'id' => $permission['id'],
                        'name' => $permission['name'],
                        'module' => $permission['module'],
                        'action' => $permission['action'],
                        'label' => $permission['label'],
                        'action_label' => $permission['action_label'],
                        'sensitive' => $permission['sensitive'],
                    ])
                    ->values()
                    ->all()),
            ])
            ->values()
            ->all());
    }

    /**
     * @return array{domain: string, domain_label: string, label: string, action_label: string, sensitive: bool}
     */
    public function metadata(string $name, ?string $module = null, ?string $action = null): array
    {
        [$parsedModule, $parsedAction] = $this->parse($name);
        $module = $module ?: $parsedModule;
        $action = $action ?: $parsedAction;
        $domain = self::MODULE_DOMAINS[$module] ?? 'other';
        $actionLabel = self::ACTION_LABELS[$action]
            ?? 'Ação não catalogada: '.Str::of($action)->replace(['.', '_'], ' ')->lower()->ucfirst();
        $moduleLabel = self::MODULE_LABELS[$module]
            ?? 'módulo '.Str::of($module)->replace('_', ' ')->lower();

        return [
            'domain' => $domain,
            'domain_label' => self::DOMAIN_LABELS[$domain],
            'label' => self::PERMISSION_LABEL_OVERRIDES[$name] ?? trim($actionLabel.' '.$moduleLabel),
            'action_label' => $actionLabel,
            'sensitive' => $this->isSensitive($name, $module, $action),
        ];
    }

    public function isSensitive(string $name, ?string $module = null, ?string $action = null): bool
    {
        if ($name === '*') {
            return true;
        }

        [$parsedModule, $parsedAction] = $this->parse($name);
        $module = $module ?: $parsedModule;
        $action = $action ?: $parsedAction;

        return in_array($module, self::SENSITIVE_MODULES, true)
            || in_array($action, self::SENSITIVE_ACTIONS, true)
            || Str::contains($action, ['sensitive', 'financial', 'nominal', 'anonymization', 'dpo'])
            || in_array($name, self::SENSITIVE_PERMISSION_PREFIXES, true);
    }

    /** @return array{0: string, 1: string} */
    private function parse(string $name): array
    {
        if (! str_contains($name, '.')) {
            return [$name, $name];
        }

        return [Str::before($name, '.'), Str::after($name, '.')];
    }
}
