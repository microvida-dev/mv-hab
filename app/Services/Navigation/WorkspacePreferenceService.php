<?php

namespace App\Services\Navigation;

use App\Models\User;
use App\Models\UserWorkspacePreference;

class WorkspacePreferenceService
{
    public function forUser(User $user): UserWorkspacePreference
    {
        /** @var UserWorkspacePreference $preference */
        $preference = UserWorkspacePreference::query()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'collapsed_groups' => [],
            'hidden_modules' => [],
            'dashboard_layout' => [],
            'workspace_layout' => [],
            'settings' => [],
        ]);

        return $preference;
    }

    /**
     * @return array<string, mixed>
     */
    public function payloadFor(User $user): array
    {
        $preference = $this->forUser($user);

        return [
            'preferred_workspace' => $preference->preferred_workspace,
            'collapsed_groups' => $preference->collapsed_groups ?? [],
            'hidden_modules' => $preference->hidden_modules ?? [],
            'dashboard_layout' => $preference->dashboard_layout ?? [],
            'workspace_layout' => $preference->workspace_layout ?? [],
            'settings' => $preference->settings ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): UserWorkspacePreference
    {
        $preference = $this->forUser($user);

        $preference->fill([
            'preferred_workspace' => $data['preferred_workspace'] ?? $preference->preferred_workspace,
            'collapsed_groups' => $data['collapsed_groups'] ?? $preference->collapsed_groups,
            'hidden_modules' => $data['hidden_modules'] ?? $preference->hidden_modules,
            'dashboard_layout' => $data['dashboard_layout'] ?? $preference->dashboard_layout,
            'workspace_layout' => $data['workspace_layout'] ?? $preference->workspace_layout,
            'settings' => $data['settings'] ?? $preference->settings,
        ]);

        $preference->save();

        return $preference;
    }
}
