<?php

namespace App\Services\Allocation;

use App\Models\Allocation;
use App\Models\Application;
use Illuminate\Database\Eloquent\Builder;

class ContractReadinessService
{
    public function allocationIsReady(Allocation $allocation): bool
    {
        return Allocation::query()->readyForContract()->whereKey($allocation->id)->exists();
    }

    /** @return Builder<Allocation> */
    public function readyAllocations(): Builder
    {
        return Allocation::query()->readyForContract()->with(['application', 'housingUnit', 'candidate']);
    }

    /** @return Builder<Application> */
    public function readyApplications(): Builder
    {
        return Application::query()->readyForContract();
    }
}
