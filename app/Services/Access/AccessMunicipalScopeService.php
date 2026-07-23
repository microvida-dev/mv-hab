<?php

namespace App\Services\Access;

use App\Models\AccessChangeEvent;
use App\Models\MunicipalTeam;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AccessMunicipalScopeService
{
    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function users(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    public function ownsUser(User $actor, User $target): bool
    {
        return $this->users(User::query()->whereKey($target), $actor)->exists();
    }

    /**
     * System roles are shared read-only definitions. Municipal roles are
     * visible only inside the authenticated user's municipality.
     *
     * @param  Builder<Role>  $query
     * @return Builder<Role>
     */
    public function roles(Builder $query, User $actor): Builder
    {
        if ($actor->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $roles) use ($actor): void {
            $roles
                ->where('is_system', true)
                ->orWhere('municipality_id', $actor->municipality_id);
        });
    }

    public function ownsRole(User $actor, Role $role): bool
    {
        return $this->roles(Role::query()->whereKey($role), $actor)->exists();
    }

    public function ownsMutableRole(User $actor, Role $role): bool
    {
        return ! $role->isSystem()
            && $actor->municipality_id !== null
            && (int) $role->municipality_id === (int) $actor->municipality_id;
    }

    /**
     * @param  Builder<MunicipalTeam>  $query
     * @return Builder<MunicipalTeam>
     */
    public function teams(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
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
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function forMunicipality(Builder $query, User $actor): Builder
    {
        if ($actor->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('municipality_id', $actor->municipality_id);
    }
}
