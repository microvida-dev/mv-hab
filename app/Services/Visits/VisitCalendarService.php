<?php

namespace App\Services\Visits;

use App\Models\HousingVisit;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VisitCalendarService
{
    /**
     * @return Collection<int, HousingVisit>
     */
    public function candidateCalendar(User $candidate, ?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return $this->baseQuery($from, $to)
            ->forCandidate($candidate)
            ->with(['application', 'contest', 'housingUnit', 'slot'])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return Collection<int, HousingVisit>
     */
    public function backofficeCalendar(?CarbonInterface $from = null, ?CarbonInterface $to = null, ?int $staffUserId = null): Collection
    {
        return $this->baseQuery($from, $to)
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
