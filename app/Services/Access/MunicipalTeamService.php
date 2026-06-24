<?php

namespace App\Services\Access;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Policies\TeamManagementPolicy;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MunicipalTeamService
{
    public function __construct(
        private readonly AccessChangeLogger $logger,
        private readonly TeamManagementPolicy $policy,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): MunicipalTeam
    {
        if (! $this->policy->create($actor)) {
            throw new AuthorizationException('Sem permissão para criar equipas.');
        }

        return DB::transaction(function () use ($actor, $data): MunicipalTeam {
            $team = MunicipalTeam::query()->create([
                'name' => (string) $data['name'],
                'slug' => Str::slug((string) $data['name']),
                'description' => $data['description'] ?? null,
                'status' => (string) ($data['status'] ?? 'active'),
                'functional_scopes' => $this->normalizeScopes($data['functional_scopes'] ?? []),
                'manager_user_id' => $data['manager_user_id'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->logger->record(
                'team_created',
                $actor,
                (string) $data['justification'],
                team: $team,
                newValues: [
                    'team_id' => $team->id,
                    'status' => $team->status,
                    'functional_scopes' => $team->functional_scopes ?? [],
                ],
            );

            return $team->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, MunicipalTeam $team, array $data): MunicipalTeam
    {
        if (! $this->policy->update($actor, $team)) {
            throw new AuthorizationException('Sem permissão para atualizar equipas.');
        }

        return DB::transaction(function () use ($actor, $team, $data): MunicipalTeam {
            $team->refresh();
            $oldValues = [
                'status' => $team->status,
                'functional_scopes' => $team->functional_scopes ?? [],
                'manager_user_id' => $team->manager_user_id,
            ];

            $team->fill([
                'name' => (string) $data['name'],
                'slug' => Str::slug((string) $data['name']),
                'description' => $data['description'] ?? null,
                'status' => (string) ($data['status'] ?? 'active'),
                'functional_scopes' => $this->normalizeScopes($data['functional_scopes'] ?? []),
                'manager_user_id' => $data['manager_user_id'] ?? null,
                'updated_by' => $actor->id,
            ])->save();

            $this->logger->record(
                'team_updated',
                $actor,
                (string) $data['justification'],
                team: $team,
                oldValues: $oldValues,
                newValues: [
                    'status' => $team->status,
                    'functional_scopes' => $team->functional_scopes ?? [],
                    'manager_user_id' => $team->manager_user_id,
                ],
            );

            return $team->refresh();
        });
    }

    public function addMember(User $actor, MunicipalTeam $team, User $member, string $justification, ?string $roleInTeam = null): void
    {
        if (! $this->policy->manageMembers($actor, $team)) {
            throw new AuthorizationException('Sem permissão para gerir membros de equipas.');
        }

        if (! $team->isActive()) {
            throw new DomainException('Equipas inativas não podem receber novas atribuições.');
        }

        DB::transaction(function () use ($actor, $team, $member, $justification, $roleInTeam): void {
            $team->members()->syncWithoutDetaching([
                $member->id => [
                    'role_in_team' => $roleInTeam,
                    'joined_at' => now(),
                    'left_at' => null,
                    'created_by' => $actor->id,
                ],
            ]);

            $this->logger->record(
                'team_member_added',
                $actor,
                $justification,
                target: $member,
                team: $team,
                newValues: [
                    'team_id' => $team->id,
                    'role_in_team' => $roleInTeam,
                ],
            );
        });
    }

    public function removeMember(User $actor, MunicipalTeam $team, User $member, string $justification): void
    {
        if (! $this->policy->manageMembers($actor, $team)) {
            throw new AuthorizationException('Sem permissão para gerir membros de equipas.');
        }

        DB::transaction(function () use ($actor, $team, $member, $justification): void {
            $team->members()->updateExistingPivot($member->id, ['left_at' => now()]);
            $team->members()->detach($member->id);

            $this->logger->record(
                'team_member_removed',
                $actor,
                $justification,
                target: $member,
                team: $team,
                oldValues: [
                    'team_id' => $team->id,
                ],
            );
        });
    }

    /**
     * @return array<int, string>
     */
    private function normalizeScopes(mixed $scopes): array
    {
        if (is_string($scopes)) {
            $scopes = preg_split('/[\r\n,]+/', $scopes) ?: [];
        }

        if (! is_array($scopes)) {
            return [];
        }

        return collect($scopes)
            ->map(fn (mixed $scope): string => trim((string) $scope))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
