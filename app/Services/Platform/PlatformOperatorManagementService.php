<?php

namespace App\Services\Platform;

use App\Data\Platform\PlatformOperatorBootstrapManifest;
use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\PlatformOperatorGrantSource;
use App\Enums\PlatformOperatorStatus;
use App\Models\MfaDevice;
use App\Models\Permission;
use App\Models\PlatformOperatorAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Security\MfaEnforcementService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformOperatorManagementService
{
    private const MINIMUM_PERMISSION = 'platform_operators.view';

    public function __construct(
        private readonly PlatformOperatorScopeService $scope,
        private readonly MfaEnforcementService $mfa,
        private readonly AuditTrailService $audit,
    ) {}

    /**
     * @return list<array{user_id: int, status: string}>
     */
    public function planBootstrap(PlatformOperatorBootstrapManifest $manifest): array
    {
        $users = $this->bootstrapUsers($manifest->approvedUserIds);
        $assignments = PlatformOperatorAssignment::query()
            ->whereIn('user_id', $manifest->approvedUserIds)
            ->get()
            ->keyBy('user_id');

        return array_map(function (int $userId) use ($users, $assignments): array {
            $user = $users->get($userId);

            if (! $user instanceof User) {
                throw new DomainException("O utilizador aprovado com ID {$userId} não existe.");
            }

            $this->assertEligibleTarget($user);

            $assignment = $assignments->get($userId);

            if ($assignment instanceof PlatformOperatorAssignment && ! $assignment->isActive()) {
                throw new DomainException(
                    "O utilizador aprovado com ID {$userId} tem uma associação revogada; reativação automática não é suportada.",
                );
            }

            return [
                'user_id' => $userId,
                'status' => $assignment instanceof PlatformOperatorAssignment
                    ? 'already_active'
                    : 'ready',
            ];
        }, $manifest->approvedUserIds);
    }

    /**
     * @return list<PlatformOperatorAssignment>
     */
    public function bootstrap(PlatformOperatorBootstrapManifest $manifest): array
    {
        return DB::transaction(function () use ($manifest): array {
            $users = $this->bootstrapUsers($manifest->approvedUserIds, true);
            $existing = PlatformOperatorAssignment::query()
                ->whereIn('user_id', $manifest->approvedUserIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('user_id');
            $assignments = [];

            foreach ($manifest->approvedUserIds as $userId) {
                $user = $users->get($userId);

                if (! $user instanceof User) {
                    throw new DomainException("O utilizador aprovado com ID {$userId} não existe.");
                }

                $this->assertEligibleTarget($user);
                $assignment = $existing->get($userId);

                if ($assignment instanceof PlatformOperatorAssignment) {
                    if (! $assignment->isActive()) {
                        throw new DomainException(
                            "O utilizador aprovado com ID {$userId} tem uma associação revogada; reativação automática não é suportada.",
                        );
                    }

                    $assignments[] = $assignment;

                    continue;
                }

                $assignment = PlatformOperatorAssignment::query()->create([
                    'user_id' => $userId,
                    'status' => PlatformOperatorStatus::Active,
                    'grant_source' => PlatformOperatorGrantSource::Bootstrap,
                    'granted_by' => null,
                    'granted_at' => now(),
                    'grant_justification' => $manifest->bootstrapOperatorReference,
                    'approval_reference_primary' => $manifest->primaryApprovalReference(),
                    'approval_reference_secondary' => $manifest->secondaryApprovalReference(),
                    'revoked_by' => null,
                    'revoked_at' => null,
                    'revoke_justification' => null,
                ]);

                $this->auditAssignment(
                    eventCode: 'platform_operator_bootstrapped',
                    assignment: $assignment,
                    target: $user,
                    actor: null,
                    before: null,
                    after: PlatformOperatorStatus::Active,
                    justification: $manifest->bootstrapOperatorReference,
                    approvalReferences: [
                        $manifest->primaryApprovalReference(),
                        $manifest->secondaryApprovalReference(),
                    ],
                );

                $assignments[] = $assignment;
            }

            return $assignments;
        });
    }

    public function grant(User $actor, User $target, string $justification): PlatformOperatorAssignment
    {
        $justification = $this->validatedJustification($justification);

        if ($actor->is($target)) {
            throw new AuthorizationException('Não é permitido conceder scope de plataforma à própria conta.');
        }

        $this->assertManagingActor($actor);

        return DB::transaction(function () use ($actor, $target, $justification): PlatformOperatorAssignment {
            $users = User::query()
                ->whereKey([(int) $actor->getKey(), (int) $target->getKey()])
                ->with(['roles.permissions', 'mfaDevices'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $lockedActor = $users->get((int) $actor->getKey());
            $lockedTarget = $users->get((int) $target->getKey());

            if (! $lockedActor instanceof User || ! $lockedTarget instanceof User) {
                throw new DomainException('O operador ou o utilizador alvo deixou de existir.');
            }

            $this->assertManagingActor($lockedActor);
            $this->assertEligibleTarget($lockedTarget);

            $existing = PlatformOperatorAssignment::query()
                ->where('user_id', $lockedTarget->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing instanceof PlatformOperatorAssignment) {
                if ($existing->isActive()) {
                    return $existing;
                }

                throw new DomainException('A associação foi revogada e não pode ser reativada automaticamente.');
            }

            $assignment = PlatformOperatorAssignment::query()->create([
                'user_id' => $lockedTarget->getKey(),
                'status' => PlatformOperatorStatus::Active,
                'grant_source' => PlatformOperatorGrantSource::PlatformOperator,
                'granted_by' => $lockedActor->getKey(),
                'granted_at' => now(),
                'grant_justification' => $justification,
                'approval_reference_primary' => null,
                'approval_reference_secondary' => null,
                'revoked_by' => null,
                'revoked_at' => null,
                'revoke_justification' => null,
            ]);

            $this->auditAssignment(
                eventCode: 'platform_operator_granted',
                assignment: $assignment,
                target: $lockedTarget,
                actor: $lockedActor,
                before: null,
                after: PlatformOperatorStatus::Active,
                justification: $justification,
            );

            return $assignment;
        });
    }

    public function revoke(
        User $actor,
        PlatformOperatorAssignment $assignment,
        string $justification,
    ): PlatformOperatorAssignment {
        $justification = $this->validatedJustification($justification);
        $this->assertManagingActor($actor);

        return DB::transaction(function () use ($actor, $assignment, $justification): PlatformOperatorAssignment {
            PlatformOperatorAssignment::query()
                ->active()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $lockedActor = User::query()
                ->with(['roles.permissions', 'mfaDevices'])
                ->whereKey($actor->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedAssignment = PlatformOperatorAssignment::query()
                ->with('user.roles.permissions')
                ->whereKey($assignment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertManagingActor($lockedActor);

            if (! $lockedAssignment->isActive()) {
                return $lockedAssignment;
            }

            if ($this->scope->isLastActive($lockedAssignment)) {
                throw new DomainException('Não é permitido revogar o último operador global ativo.');
            }

            $target = $lockedAssignment->user;

            if (! $target instanceof User) {
                throw new DomainException('O utilizador alvo da associação deixou de existir.');
            }

            $before = $lockedAssignment->status;
            $lockedAssignment->forceFill([
                'status' => PlatformOperatorStatus::Revoked,
                'revoked_by' => $lockedActor->getKey(),
                'revoked_at' => now(),
                'revoke_justification' => $justification,
            ])->save();

            $this->auditAssignment(
                eventCode: 'platform_operator_revoked',
                assignment: $lockedAssignment,
                target: $target,
                actor: $lockedActor,
                before: $before,
                after: PlatformOperatorStatus::Revoked,
                justification: $justification,
            );

            return $lockedAssignment->refresh();
        });
    }

    private function assertManagingActor(User $actor): void
    {
        if (! $this->scope->hasGlobalScope($actor)
            || ! $actor->hasPermission('platform_operators.manage')
            || $actor->hasRole(['candidate', 'auditor'])) {
            throw new AuthorizationException('Sem autorização para gerir operadores de plataforma.');
        }

        if (! $this->mfa->sessionVerified()) {
            throw new AuthorizationException('A gestão de operadores exige uma sessão MFA verificada.');
        }
    }

    private function assertEligibleTarget(User $target): void
    {
        if (($target->status ?? 'active') !== 'active') {
            throw new DomainException("O utilizador aprovado com ID {$target->getKey()} não está ativo.");
        }

        if ($target->municipality_id !== null) {
            throw new DomainException(
                "O utilizador aprovado com ID {$target->getKey()} está associado a um Município.",
            );
        }

        $target->loadMissing(['roles.permissions', 'mfaDevices']);

        if ($target->roles->contains(
            fn (Role $role): bool => $role->isActive() && $role->name === 'candidate',
        )) {
            throw new DomainException("O utilizador aprovado com ID {$target->getKey()} é candidato.");
        }

        if (! $this->loadedUserHasPermission($target, self::MINIMUM_PERMISSION)) {
            throw new DomainException(
                "O utilizador aprovado com ID {$target->getKey()} não possui as permissões administrativas mínimas.",
            );
        }

        if (! $target->mfaDevices->contains(
            fn (MfaDevice $device): bool => $device->confirmed_at !== null
                && $device->disabled_at === null,
        )) {
            throw new DomainException(
                "O utilizador aprovado com ID {$target->getKey()} não possui MFA confirmado.",
            );
        }
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, User>
     */
    private function bootstrapUsers(array $ids, bool $lock = false): Collection
    {
        $query = User::query()
            ->whereKey($ids)
            ->with(['roles.permissions', 'mfaDevices'])
            ->orderBy('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->keyBy('id');
    }

    private function loadedUserHasPermission(User $user, string $permission): bool
    {
        [$module, $action] = str_contains($permission, '.')
            ? explode('.', $permission, 2)
            : [$permission, null];

        return $user->roles
            ->filter(fn (Role $role): bool => $role->isActive())
            ->contains(fn (Role $role): bool => $role->permissions->contains(
                fn (Permission $candidate): bool => $candidate->name === '*'
                    || $candidate->name === $permission
                    || $candidate->name === $module.'.*'
                    || ($action !== null && $candidate->name === '*.'.$action),
            ));
    }

    private function validatedJustification(string $justification): string
    {
        $justification = trim($justification);

        if (mb_strlen($justification) < 10 || mb_strlen($justification) > 1000) {
            throw new DomainException('A justificação deve ter entre 10 e 1000 caracteres.');
        }

        if ($justification !== strip_tags($justification)) {
            throw new DomainException('A justificação não pode conter HTML.');
        }

        return $justification;
    }

    /**
     * @param  list<string>  $approvalReferences
     */
    private function auditAssignment(
        string $eventCode,
        PlatformOperatorAssignment $assignment,
        User $target,
        ?User $actor,
        ?PlatformOperatorStatus $before,
        PlatformOperatorStatus $after,
        string $justification,
        array $approvalReferences = [],
    ): void {
        $this->audit->record(
            eventCode: $eventCode,
            auditable: $assignment,
            category: AuditEventCategory::Security,
            severity: AuditEventSeverity::Info,
            description: match ($eventCode) {
                'platform_operator_bootstrapped' => 'Operador de plataforma inicializado por manifesto aprovado.',
                'platform_operator_granted' => 'Scope global de operador de plataforma concedido.',
                default => 'Scope global de operador de plataforma revogado.',
            },
            oldValues: ['status' => $before?->value],
            newValues: ['status' => $after->value],
            metadata: [
                'assignment_id' => $assignment->getKey(),
                'target_user_id' => $target->getKey(),
                'actor_id' => $actor?->getKey(),
                'grant_source' => $assignment->grant_source->value,
                'before' => $before?->value,
                'after' => $after->value,
                'approval_references' => $approvalReferences,
                'justification' => $justification,
                'operation_id' => (string) Str::uuid(),
                'timestamp' => now()->toIso8601String(),
            ],
            subject: $target,
            actor: $actor,
            useAuthenticatedUser: false,
        );
    }
}
