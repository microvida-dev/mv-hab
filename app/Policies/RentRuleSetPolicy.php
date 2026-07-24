<?php

namespace App\Policies;

use App\Models\RentRuleSet;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class RentRuleSetPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'contracts', 'view');
    }

    public function view(User $user, RentRuleSet $rentRuleSet): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'create');
    }

    public function update(User $user, RentRuleSet $rentRuleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function activate(User $user, RentRuleSet $rentRuleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'approve');
    }

    public function archive(User $user, RentRuleSet $rentRuleSet): bool
    {
        return $this->update($user, $rentRuleSet);
    }

    public function duplicate(User $user, RentRuleSet $rentRuleSet): bool
    {
        return $this->create($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'finance', 'rent_rule_sets.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, RentRuleSet $ruleSet): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsRentRuleSet($user, $ruleSet);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canMutateBackoffice($user, 'rent_rule_sets.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function updateBackoffice(User $user, RentRuleSet $ruleSet): bool
    {
        return $this->canMutateBackoffice($user, 'rent_rule_sets.update')
            && $this->municipalScope->ownsRentRuleSet($user, $ruleSet);
    }

    public function activateBackoffice(User $user, RentRuleSet $ruleSet): bool
    {
        return $this->canMutateBackoffice($user, 'rent_rule_sets.activate')
            && $this->municipalScope->ownsRentRuleSet($user, $ruleSet);
    }

    public function archiveBackoffice(User $user, RentRuleSet $ruleSet): bool
    {
        return $this->canMutateBackoffice($user, 'rent_rule_sets.archive')
            && $this->municipalScope->ownsRentRuleSet($user, $ruleSet);
    }

    public function duplicateBackoffice(User $user, RentRuleSet $ruleSet): bool
    {
        return $this->canMutateBackoffice($user, 'rent_rule_sets.duplicate')
            && $this->municipalScope->ownsRentRuleSet($user, $ruleSet);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'finance', $action);
    }
}
