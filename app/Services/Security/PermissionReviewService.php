<?php

namespace App\Services\Security;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\PermissionReview;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Support\Str;

class PermissionReviewService
{
    public function __construct(private readonly AuditTrailService $audit) {}

    public function create(User $actor, string $scope = 'all'): PermissionReview
    {
        $review = PermissionReview::query()->create([
            'review_number' => 'PERM-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'status' => 'in_progress',
            'scope' => $scope,
            'started_by' => $actor->id,
            'started_at' => now(),
            'summary' => 'Revisão de permissões gerada automaticamente. DEMO — SUJEITO A VALIDAÇÃO DO MUNICÍPIO/DPO.',
            'findings' => [],
            'recommendations' => [],
        ]);

        $this->populateItems($review);
        $this->audit->record('permission_review.created', $review, AuditEventCategory::Security, AuditEventSeverity::Notice, 'Revisão de permissões criada.', actor: $actor);

        return $review->refresh();
    }

    public function complete(PermissionReview $review, User $actor, ?string $summary = null): PermissionReview
    {
        $review->forceFill([
            'status' => 'completed',
            'completed_by' => $actor->id,
            'completed_at' => now(),
            'summary' => $summary ?: $review->summary,
        ])->save();

        $this->audit->record('permission_review.completed', $review, AuditEventCategory::Security, AuditEventSeverity::Notice, 'Revisão de permissões concluída.', actor: $actor);

        return $review->refresh();
    }

    private function populateItems(PermissionReview $review): void
    {
        User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['administrator', 'municipal_technician', 'jury', 'financial_manager', 'maintenance_manager', 'auditor']))
            ->with('roles', 'mfaDevices')
            ->get()
            ->each(function (User $user) use ($review): void {
                if (! $user->mfaDevices()->whereNotNull('confirmed_at')->whereNull('disabled_at')->exists()) {
                    $review->items()->create([
                        'user_id' => $user->id,
                        'module' => 'mfa',
                        'risk_level' => $user->hasRole('administrator') ? 'high' : 'medium',
                        'finding' => 'Utilizador backoffice sem MFA confirmado.',
                        'recommendation' => 'Exigir configuração de MFA antes de acesso sensível.',
                    ]);
                }
            });

        Role::with('permissions')->get()->each(function (Role $role) use ($review): void {
            if ($role->permissions->contains('name', '*')) {
                $review->items()->create([
                    'role_name' => $role->name,
                    'permission_name' => '*',
                    'module' => 'permissions',
                    'risk_level' => 'high',
                    'finding' => 'Perfil com permissão global.',
                    'recommendation' => 'Confirmar necessidade operacional e manter auditoria reforçada.',
                ]);
            }
        });
    }
}
