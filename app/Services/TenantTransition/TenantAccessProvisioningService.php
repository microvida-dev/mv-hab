<?php

namespace App\Services\TenantTransition;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Finance\TenantFinancialAccountService;

class TenantAccessProvisioningService
{
    public function __construct(private readonly TenantFinancialAccountService $accounts) {}

    public function provision(?Contract $contract, User $actor): ?TenantFinancialAccount
    {
        $status = $contract === null
            ? null
            : ContractStatus::tryFrom((string) $contract->getRawOriginal('status'));

        if ($status !== ContractStatus::Active) {
            return null;
        }

        return $this->accounts->ensureForContract($contract, $actor);
    }
}
