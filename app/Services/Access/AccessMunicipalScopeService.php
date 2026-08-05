<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Models\AccessChangeEvent;
use App\Models\Municipality;
use App\Models\MunicipalTeam;
use App\Models\Role;
use App\Models\User;
use App\Services\Platform\PlatformMunicipalContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AccessMunicipalScopeService
{
    public function __construct(
        private readonly PlatformMunicipalContextService $municipalContext,
    ) {}

    public function effectiveMunicipality(User $actor): ?Municipality
    {
        return $this->municipalContext->effectiveMunicipality($actor);
    }

    public function requireMunicipality(User $actor): Municipality
    {
        return $this->municipalContext->requireMunicipality($actor);
    }

    /** @return positive-int */
    public function municipalityId(User $actor): int
    {
        $municipalityId = (int) $this->requireMunicipality($actor)->getKey();

        if ($municipalityId < 1) {
            throw new LogicException('O Município efetivo não possui um identificador válido.');
        }

        return $municipalityId;
    }

    public function hasMunicipality(User $actor): bool
    {
        return $this->effectiveMunicipality($actor) instanceof Municipality;
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function users(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    public function requireUser(User $actor, int $userId): User
    {
        return $this->users(User::query(), $actor)->findOrFail($userId);
    }

    public function ownsUser(User $actor, User $target): bool
    {
        return $this->users(User::query()->whereKey($target), $actor)->exists();
    }

    /**
     * @param  Builder<Role>  $query
     * @return Builder<Role>
     */
    public function roles(Builder $query, User $actor): Builder
    {
        $municipality = $this->effectiveMunicipality($actor);

        if (! $municipality instanceof Municipality) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $roles) use ($municipality): void {
            $roles->where('is_system', true)
                ->orWhere('municipality_id', (int) $municipality->getKey());
        });
    }

    public function requireRoleByName(User $actor, string $roleName): Role
    {
        return $this->roles(Role::query(), $actor)
            ->where('name', $roleName)
            ->firstOrFail();
    }

    public function ownsRole(User $actor, Role $role): bool
    {
        return $this->roles(Role::query()->whereKey($role), $actor)->exists();
    }

    public function ownsMutableRole(User $actor, Role $role): bool
    {
        $municipality = $this->effectiveMunicipality($actor);

        return ! $role->isSystem()
            && $municipality instanceof Municipality
            && (int) $role->municipality_id === (int) $municipality->getKey();
    }

    /**
     * @param  Builder<MunicipalTeam>  $query
     * @return Builder<MunicipalTeam>
     */
    public function teams(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    public function requireTeam(User $actor, int $teamId): MunicipalTeam
    {
        return $this->teams(MunicipalTeam::query(), $actor)->findOrFail($teamId);
    }

    public function ownsTeam(User $actor, MunicipalTeam $team): bool
    {
        return $this->teams(MunicipalTeam::query()->whereKey($team), $actor)->exists();
    }

    /**
     * @param  Builder<AccessChangeEvent>  $query
     * @return Builder<AccessChangeEvent>
     */
    public function accessEvents(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function forMunicipality(Builder $query, User $actor): Builder
    {
        $municipality = $this->effectiveMunicipality($actor);

        if (! $municipality instanceof Municipality) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('municipality_id', (int) $municipality->getKey());
    }
}
