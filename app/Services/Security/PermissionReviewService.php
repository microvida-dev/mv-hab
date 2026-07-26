<?php

namespace App\Services\Security;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\PermissionReview;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

class PermissionReviewService
{
    public function __construct(
        private readonly AuditTrailService $audit,
        private readonly MfaEnforcementService $mfa,
        private readonly SecurityMunicipalScopeService $municipalScope,
    ) {}

    public function create(User $actor, string $scope = 'all'): PermissionReview
    {
        if ($actor->municipality_id === null) {
            throw new AuthorizationException('A revisão exige contexto municipal.');
        }

        $review = PermissionReview::query()->create([
            'municipality_id' => $actor->municipality_id,
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
        if (! $this->municipalScope->ownsPermissionReview($actor, $review)) {
            throw new AuthorizationException('A revisão não pertence ao município do utilizador.');
        }

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
            ->where('municipality_id', $review->municipality_id)
            ->whereHas('roles', fn ($roles) => $roles->where('is_active', true))
            ->with('roles.permissions', 'mfaDevices')
            ->lazyById(100)
            ->each(function (User $user) use ($review): void {
                $requiresMfa = $user->roles->contains(
                    fn (Role $role): bool => $this->mfa->roleRequiresMfa($role),
                );
                $hasConfirmedDevice = $user->mfaDevices->contains(
                    fn ($device): bool => $device->confirmed_at !== null
                        && $device->disabled_at === null,
                );

                if (! $requiresMfa || $hasConfirmedDevice) {
                    return;
                }

                $review->items()->create([
                    'user_id' => $user->id,
                    'module' => 'mfa',
                    'risk_level' => 'high',
                    'finding' => 'Utilizador com acesso sensível sem MFA confirmado.',
                    'recommendation' => 'Exigir configuração de MFA antes de acesso sensível.',
                ]);
            });

        Role::query()
            ->where(function ($roles) use ($review): void {
                $roles
                    ->where('is_system', true)
                    ->orWhere('municipality_id', $review->municipality_id);
            })
            ->with('permissions')
            ->get()
            ->each(function (Role $role) use ($review): void {
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
