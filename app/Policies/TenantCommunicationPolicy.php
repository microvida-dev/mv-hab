<?php

namespace App\Policies;

use App\Models\TenantCommunication;
use App\Models\User;

class TenantCommunicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['candidate', 'administrator', 'municipal_technician', 'financial_manager', 'maintenance_manager', 'auditor']);
    }

    public function view(User $user, TenantCommunication $tenantCommunication): bool
    {
        return $user->hasRole('candidate')
            ? (int) $tenantCommunication->user_id === (int) $user->id
            : $user->hasRole(['administrator', 'municipal_technician', 'financial_manager', 'maintenance_manager', 'auditor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['candidate', 'administrator', 'municipal_technician', 'financial_manager', 'maintenance_manager']);
    }

    public function update(User $user, TenantCommunication $tenantCommunication): bool
    {
        return $this->view($user, $tenantCommunication)
            && ! $user->hasRole('auditor');
    }
}
