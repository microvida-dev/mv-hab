<?php

namespace App\Services\Security;

use App\Enums\SecurityChecklistStatus;
use App\Models\SecurityChecklist;
use App\Models\SecurityChecklistItem;
use App\Models\User;
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

    public function create(User $actor, string $environment = 'pre-production'): SecurityChecklist
    {
        $checklist = SecurityChecklist::query()->create([
            'checklist_number' => 'CHK-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
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
