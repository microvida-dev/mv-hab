<?php

namespace App\Services\Access;

use App\Models\MunicipalTeam;
use App\Models\Role;
use App\Models\User;
use App\Policies\RoleAssignmentPolicy;
use App\Policies\UserAdministrationPolicy;
use App\Services\Security\SessionRevocationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserAdministrationService
{
    private const MFA_REQUIRED_ROLES = [
        'administrator',
        'municipal_technician',
        'jury',
        'legal_manager',
        'financial_manager',
        'housing_manager',
        'inspection_manager',
        'auditor',
    ];

    public function __construct(
        private readonly AccessChangeLogger $logger,
        private readonly UserAdministrationPolicy $userPolicy,
        private readonly RoleAssignmentPolicy $rolePolicy,
        private readonly SessionRevocationService $sessions,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): User
    {
        if (! $this->userPolicy->create($actor)) {
            throw new AuthorizationException('Sem permissão para criar utilizadores.');
        }

        $role = Role::query()->where('name', (string) $data['role'])->firstOrFail();
        $this->authorizeInitialRole($actor, $role);
        $team = $this->teamFromData($data);
        $this->ensureTeamAcceptsMembers($team);

        return DB::transaction(function () use ($actor, $data, $role, $team): User {
            $user = new User;
            $user->name = (string) $data['name'];
            $user->email = (string) $data['email'];
            $user->email_verified_at = now();
            $user->password = Str::password(40);
            $user->status = (string) ($data['status'] ?? 'active');
            $user->mfa_required = (bool) ($data['mfa_required'] ?? false) || $this->roleRequiresMfa($role->name);
            $user->internal_notes = $data['internal_notes'] ?? null;
            $user->save();

            $user->roles()->syncWithoutDetaching([$role->id]);

            if ($team instanceof MunicipalTeam) {
                $user->municipalTeams()->syncWithoutDetaching([
                    $team->id => [
                        'role_in_team' => $data['role_in_team'] ?? null,
                        'joined_at' => now(),
                        'left_at' => null,
                        'created_by' => $actor->id,
                    ],
                ]);

                $this->logger->record(
                    'team_member_added',
                    $actor,
                    (string) $data['justification'],
                    target: $user,
                    team: $team,
                    newValues: [
                        'team_id' => $team->id,
                        'team_status' => $team->status,
                    ],
                );
            }

            $this->logger->record(
                'user_created',
                $actor,
                (string) $data['justification'],
                target: $user,
                role: $role,
                team: $team,
                newValues: [
                    'status' => $user->status,
                    'mfa_required' => $user->mfa_required,
                    'role_names' => [$role->name],
                    'team_ids' => $team instanceof MunicipalTeam ? [$team->id] : [],
                ],
            );

            return $user->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, User $target, array $data): User
    {
        if (! $this->userPolicy->update($actor, $target)) {
            throw new AuthorizationException('Sem permissão para atualizar utilizadores.');
        }

        return DB::transaction(function () use ($actor, $target, $data): User {
            $target->refresh();

            $target->fill(Arr::only($data, ['name', 'internal_notes', 'mfa_required']));

            if (! $target->isDirty()) {
                return $target;
            }

            $changes = array_keys($target->getDirty());
            $oldValues = $this->minimizedValues($target, $changes, fromOriginal: true);
            $target->save();
            $newValues = $this->minimizedValues($target, $changes, fromOriginal: false);

            $this->logger->record(
                'user_updated',
                $actor,
                (string) $data['justification'],
                target: $target,
                oldValues: $oldValues,
                newValues: $newValues,
            );

            return $target->refresh();
        });
    }

    public function deactivate(User $actor, User $target, string $justification): User
    {
        if (! $this->userPolicy->deactivate($actor, $target)) {
            throw new AuthorizationException('Sem permissão para desativar utilizadores.');
        }

        return DB::transaction(function () use ($actor, $target, $justification): User {
            $target->refresh();

            if ($actor->is($target) && $target->hasRole('administrator')) {
                throw new AuthorizationException('Auto-desativação crítica bloqueada.');
            }

            if ($this->isLastActiveAdministrator($target)) {
                throw new DomainException('Não é permitido desativar o último administrator ativo.');
            }

            $oldValues = [
                'status' => $target->status,
                'deactivated_at' => null,
            ];

            $target->forceFill([
                'status' => 'inactive',
                'deactivated_at' => now(),
                'deactivated_by' => $actor->id,
            ])->save();

            $revokedSessions = $this->sessions->revokeAllForUser($target, $actor, 'Utilizador desativado; sessões revogadas.');

            $this->logger->record(
                'user_deactivated',
                $actor,
                $justification,
                target: $target,
                oldValues: $oldValues,
                newValues: [
                    'status' => $target->status,
                    'deactivated_by' => $actor->id,
                    'revoked_sessions_count' => $revokedSessions,
                ],
            );

            $this->logger->record(
                'user_deactivated_session_revoked',
                $actor,
                $justification,
                target: $target,
                newValues: [
                    'revoked_sessions_count' => $revokedSessions,
                ],
            );

            return $target->refresh();
        });
    }

    public function reactivate(User $actor, User $target, string $justification): User
    {
        if (! $this->userPolicy->reactivate($actor, $target)) {
            throw new AuthorizationException('Sem permissão para reativar utilizadores.');
        }

        return DB::transaction(function () use ($actor, $target, $justification): User {
            $target->refresh();
            $oldValues = ['status' => $target->status];

            $target->forceFill([
                'status' => 'active',
                'reactivated_at' => now(),
                'reactivated_by' => $actor->id,
            ])->save();

            $this->logger->record(
                'user_reactivated',
                $actor,
                $justification,
                target: $target,
                oldValues: $oldValues,
                newValues: [
                    'status' => $target->status,
                    'reactivated_by' => $actor->id,
                ],
            );

            return $target->refresh();
        });
    }

    public function forceMfa(User $actor, User $target, string $justification): User
    {
        if (! $this->userPolicy->forceMfa($actor, $target)) {
            throw new AuthorizationException('Sem permissão para impor MFA.');
        }

        return DB::transaction(function () use ($actor, $target, $justification): User {
            $target->refresh();

            if ($target->mfa_required) {
                return $target;
            }

            $target->forceFill(['mfa_required' => true])->save();

            $this->logger->record(
                'user_mfa_enforced',
                $actor,
                $justification,
                target: $target,
                oldValues: ['mfa_required' => false],
                newValues: ['mfa_required' => true],
            );
            $this->logger->record(
                'mfa_enforced',
                $actor,
                $justification,
                target: $target,
                oldValues: ['mfa_required' => false],
                newValues: ['mfa_required' => true],
            );

            return $target->refresh();
        });
    }

    public function requestPasswordReset(User $actor, User $target, string $justification): string
    {
        if (! $this->userPolicy->resetPassword($actor, $target)) {
            throw new AuthorizationException('Sem permissão para reset seguro de acesso.');
        }

        $status = Password::sendResetLink(['email' => $target->email]);

        $this->logger->record(
            'user_password_reset_requested',
            $actor,
            $justification,
            target: $target,
            newValues: [
                'notification_status' => $status,
            ],
        );

        return $status;
    }

    private function authorizeInitialRole(User $actor, Role $role): void
    {
        if (! $this->rolePolicy->assign($actor, $role)) {
            throw new AuthorizationException('Sem permissão para definir a role inicial.');
        }

        if (! $this->roleIsWithinActorPermissions($actor, $role)) {
            throw new AuthorizationException('A role inicial excede o escopo de permissões do actor.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function teamFromData(array $data): ?MunicipalTeam
    {
        if (empty($data['team_id'])) {
            return null;
        }

        return MunicipalTeam::query()->findOrFail((int) $data['team_id']);
    }

    private function ensureTeamAcceptsMembers(?MunicipalTeam $team): void
    {
        if ($team instanceof MunicipalTeam && ! $team->isActive()) {
            throw new DomainException('Equipas inativas não podem receber novas atribuições.');
        }
    }

    private function roleRequiresMfa(string $roleName): bool
    {
        return in_array($roleName, self::MFA_REQUIRED_ROLES, true);
    }

    private function roleIsWithinActorPermissions(User $actor, Role $role): bool
    {
        if ($actor->hasPermission('*')) {
            return true;
        }

        return $role->permissions()
            ->pluck('name')
            ->every(fn (string $permission): bool => $actor->hasPermission($permission));
    }

    private function isLastActiveAdministrator(User $target): bool
    {
        if (! $target->hasRole('administrator') || $target->status !== 'active') {
            return false;
        }

        return ! User::query()
            ->whereKeyNot($target->id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('name', 'administrator'))
            ->exists();
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    private function minimizedValues(User $user, array $fields, bool $fromOriginal): array
    {
        $redacted = ['name', 'internal_notes'];

        return collect($fields)
            ->mapWithKeys(function (string $field) use ($user, $fromOriginal, $redacted): array {
                if (in_array($field, $redacted, true)) {
                    return [$field => '[minimized]'];
                }

                return [$field => $fromOriginal ? $user->getOriginal($field) : $user->getAttribute($field)];
            })
            ->all();
    }
}
