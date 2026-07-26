<?php

namespace App\Policies;

use App\Models\ScoringRuleSet;
use App\Models\TieBreakerRule;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class TieBreakerRulePolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, TieBreakerRule $rule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user, ?ScoringRuleSet $ruleSet = null): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'create');
    }

    public function update(User $user, TieBreakerRule $rule): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'update');
    }

    public function activate(User $user, TieBreakerRule $rule): bool
    {
        return $this->update($user, $rule);
    }

    public function createBackoffice(User $user, ScoringRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'create')
            && $this->municipalScope->ownsScoringRuleSet($user, $ruleSet);
    }

    public function updateBackoffice(User $user, TieBreakerRule $rule): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'update')
            && $this->municipalScope->ownsTieBreakerRule($user, $rule);
    }

    public function activateBackoffice(User $user, TieBreakerRule $rule): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'activate')
            && $this->municipalScope->ownsTieBreakerRule($user, $rule);
    }

    public function deactivateBackoffice(User $user, TieBreakerRule $rule): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'deactivate')
            && $this->municipalScope->ownsTieBreakerRule($user, $rule);
    }
}
