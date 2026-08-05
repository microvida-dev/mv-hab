<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\ActorProfile;
use App\Models\User;

final class ActorProfileResolver
{
    /** @var array<string, ActorProfile> */
    private const MUNICIPAL_ROLE_PROFILES = [
        'administrator' => ActorProfile::MunicipalAdministrator,
        'municipal_technician' => ActorProfile::MunicipalTechnician,
        'jury' => ActorProfile::Jury,
        'legal_manager' => ActorProfile::LegalManager,
        'financial_manager' => ActorProfile::FinancialManager,
        'housing_manager' => ActorProfile::HousingManager,
        'maintenance_manager' => ActorProfile::MaintenanceManager,
        'inspection_manager' => ActorProfile::InspectionManager,
        'support_agent' => ActorProfile::SupportAgent,
        'auditor' => ActorProfile::Auditor,
    ];

    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    public function primary(User $user): ActorProfile
    {
        return $this->profiles($user)[0] ?? ActorProfile::Unclassified;
    }

    /** @return list<ActorProfile> */
    public function profiles(User $user): array
    {
        if (! $this->isActive($user)) {
            return [ActorProfile::Unclassified];
        }

        if ($this->platformScope->hasGlobalScope($user)) {
            return [ActorProfile::PlatformAdministrator];
        }

        $roleNames = $this->activeRoleNames($user);

        if (in_array('candidate', $roleNames, true)) {
            return [ActorProfile::Candidate];
        }

        if ($user->municipality_id === null) {
            return [ActorProfile::Unclassified];
        }

        $profiles = [];

        foreach (self::MUNICIPAL_ROLE_PROFILES as $roleName => $profile) {
            if (in_array($roleName, $roleNames, true)) {
                $profiles[] = $profile;
            }
        }

        if ($profiles === []
            && $this->hasActiveMunicipalCustomRole($user)) {
            return [ActorProfile::MunicipalTechnician];
        }

        return $profiles === []
            ? [ActorProfile::Unclassified]
            : $profiles;
    }

    private function hasActiveMunicipalCustomRole(User $user): bool
    {
        return $user->roles()
            ->where('roles.is_active', true)
            ->where('roles.is_system', false)
            ->where('roles.scope', 'municipal')
            ->where('roles.municipality_id', $user->municipality_id)
            ->exists();
    }

    private function isActive(User $user): bool
    {
        return $user->deactivated_at === null
            && ($user->status ?? 'active') === 'active';
    }

    /** @return list<string> */
    private function activeRoleNames(User $user): array
    {
        return array_values(
            $user->roles()
                ->where('roles.is_active', true)
                ->orderBy('roles.id')
                ->pluck('roles.name')
                ->filter(fn (mixed $name): bool => is_string($name))
                ->all(),
        );
    }
}
