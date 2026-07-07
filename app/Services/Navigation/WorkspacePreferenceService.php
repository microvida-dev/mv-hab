<?php

namespace App\Services\Navigation;

use App\Models\User;
use App\Models\UserWorkspacePreference;

class WorkspacePreferenceService
{
    public function __construct(private readonly WorkspaceService $workspaces) {}

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
        if (array_key_exists('preferred_workspace', $data) && is_string($data['preferred_workspace'])) {
            $workspace = $this->workspaces->authorizedWorkspace($user, $data['preferred_workspace']);

            if ($workspace === null) {
                unset($data['preferred_workspace']);
            }
        }

        $preference = $this->forUser($user);

        $preference->fill([
            'preferred_workspace' => array_key_exists('preferred_workspace', $data)
                ? $data['preferred_workspace']
                : $preference->preferred_workspace,
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
