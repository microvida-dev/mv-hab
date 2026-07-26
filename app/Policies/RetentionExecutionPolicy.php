<?php

namespace App\Policies;

use App\Models\RetentionExecution;
use App\Models\User;
use App\Services\Rgpd\PrivacyMunicipalScopeService;

class RetentionExecutionPolicy
{
    public function __construct(
        private readonly PrivacyMunicipalScopeService $scope,
    ) {}

    public function view(User $user, RetentionExecution $execution): bool
    {
        return $user->hasPermission('rgpd.retention.view')
            && $this->scope->ownsRetentionExecution($user, $execution);
    }

    public function approve(User $user, RetentionExecution $execution): bool
    {
        return $user->hasPermission('rgpd.retention.approve')
            && $this->scope->ownsRetentionExecution($user, $execution);
    }

    public function execute(User $user, RetentionExecution $execution): bool
    {
        return $user->hasPermission('rgpd.retention.execute')
            && $this->scope->ownsRetentionExecution($user, $execution);
    }
}
