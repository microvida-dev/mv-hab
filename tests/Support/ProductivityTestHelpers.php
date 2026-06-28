<?php

namespace Tests\Support;

use App\Models\MunicipalTeam;
use App\Models\User;

trait ProductivityTestHelpers
{
    protected function backofficeUser(string $role = 'administrator', ?MunicipalTeam $team = null, string $name = 'Utilizador UX06'): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        if ($team instanceof MunicipalTeam) {
            $team->members()->syncWithoutDetaching([
                $user->id => ['joined_at' => now(), 'role_in_team' => $role],
            ]);
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    protected function verifiedBackofficeSession(): array
    {
        return ['mfa.verified_at' => now()];
    }
}
