<?php

namespace App\Services\Security;

use App\Enums\SecurityChecklistStatus;
use App\Models\SecurityChecklist;
use App\Models\SecurityChecklistItem;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use RuntimeException;

class PreProductionSecurityChecklistService
{
    public const CATEGORIES = [
        'authentication',
        'mfa',
        'permissions',
        'storage',
        'documents',
        'audit',
        'access_logs',
        'exports',
        'backups',
        'passwords',
        'sessions',
        'security_headers',
        'rgpd',
        'retention',
        'alerts',
        'production_config',
    ];

    public function __construct(
        private readonly SecurityMunicipalScopeService $municipalScope,
    ) {}

    public function create(User $actor, string $environment = 'pre-production'): SecurityChecklist
    {
        if ($actor->municipality_id === null) {
            throw new AuthorizationException('A checklist exige contexto municipal.');
        }

        $checklist = SecurityChecklist::query()->create([
            'checklist_number' => 'CHK-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'municipality_id' => $actor->municipality_id,
            'name' => 'Checklist de segurança pré-produção',
            'status' => SecurityChecklistStatus::InProgress,
            'environment' => $environment,
            'started_by' => $actor->id,
            'started_at' => now(),
            'summary' => 'DEMO — SUJEITO A VALIDAÇÃO DO MUNICÍPIO/DPO.',
        ]);

        foreach (self::CATEGORIES as $category) {
            $checklist->items()->create([
                'category' => $category,
                'title' => str($category)->replace('_', ' ')->title()->toString(),
                'description' => 'Validar controlos antes de produção.',
                'status' => SecurityChecklistStatus::Draft,
                'recommendation' => 'Recolher evidência operacional e aprovação responsável.',
            ]);
        }

        return $checklist->refresh();
    }

    public function updateItem(SecurityChecklistItem $item, User $actor, string $status, ?string $evidence = null): SecurityChecklistItem
    {
        if (! $this->municipalScope->ownsChecklistItem($actor, $item)) {
            throw new AuthorizationException('O item não pertence ao município do utilizador.');
        }

        $item->forceFill([
            'status' => SecurityChecklistStatus::from($status),
            'evidence' => $evidence,
            'checked_by' => $actor->id,
            'checked_at' => now(),
        ])->save();

        return $item->refresh();
    }

    public function approve(SecurityChecklist $checklist, User $actor): SecurityChecklist
    {
        if (! $this->municipalScope->ownsChecklist($actor, $checklist)) {
            throw new AuthorizationException('A checklist não pertence ao município do utilizador.');
        }

        if ($checklist->items()->where('status', SecurityChecklistStatus::Failed->value)->exists()) {
            throw new RuntimeException('Não é possível aprovar checklist com itens falhados.');
        }

        $checklist->forceFill([
            'status' => SecurityChecklistStatus::Approved,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ])->save();

        return $checklist->refresh();
    }
}
