<?php

namespace App\Services\Visits;

use App\Models\HousingVisit;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VisitCalendarService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @return Collection<int, HousingVisit>
     */
    public function backofficeCalendar(
        User $actor,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
        ?int $staffUserId = null,
    ): Collection {
        return $this->municipalScope
            ->housingVisits(
                $this->baseQuery($from, $to),
                $actor,
            )
            ->when($staffUserId !== null, fn (Builder $query): Builder => $query->where('staff_user_id', $staffUserId))
            ->with(['candidate', 'application', 'contest', 'housingUnit'])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return Builder<HousingVisit>
     */
    private function baseQuery(?CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        return HousingVisit::query()
            ->when($from !== null, fn (Builder $query): Builder => $query->where('starts_at', '>=', $from))
            ->when($to !== null, fn (Builder $query): Builder => $query->where('starts_at', '<=', $to));
    }
}
