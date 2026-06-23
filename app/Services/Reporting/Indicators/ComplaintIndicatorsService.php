<?php

namespace App\Services\Reporting\Indicators;

use App\Enums\ComplaintStatus;
use App\Enums\HearingStatus;
use App\Models\Complaint;
use App\Models\Hearing;
use App\Services\Reporting\ReportFilterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ComplaintIndicatorsService
{
    public function __construct(private readonly ReportFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Complaint>
     */
    private function query(array $filters): Builder
    {
        return $this->filters->applyThroughApplication(Complaint::query(), $filters, 'submitted_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countSubmittedComplaints(array $filters): int
    {
        return $this->query($filters)->where('status', ComplaintStatus::Submitted->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countComplaintsUnderReview(array $filters): int
    {
        return $this->query($filters)->where('status', ComplaintStatus::UnderReview->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countAcceptedComplaints(array $filters): int
    {
        return $this->query($filters)->where('status', ComplaintStatus::Accepted->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countRejectedComplaints(array $filters): int
    {
        return $this->query($filters)->where('status', ComplaintStatus::Rejected->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countPartiallyAcceptedComplaints(array $filters): int
    {
        return $this->query($filters)->where('status', ComplaintStatus::PartiallyAccepted->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function averageComplaintDecisionTime(array $filters): float
    {
        $query = $this->query($filters)->whereNotNull('submitted_at')->whereNotNull('review_completed_at');
        $expression = DB::connection()->getDriverName() === 'sqlite'
            ? 'AVG(julianday(review_completed_at) - julianday(submitted_at))'
            : 'AVG(TIMESTAMPDIFF(SECOND, submitted_at, review_completed_at)) / 86400';

        return round((float) $query->selectRaw("$expression as average_days")->value('average_days'), 2);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countPendingHearings(array $filters): int
    {
        return $this->filters->applyThroughApplication(Hearing::query(), $filters, 'issued_at')
            ->whereIn('status', [HearingStatus::Issued->value, HearingStatus::Open->value, HearingStatus::Submitted->value, HearingStatus::UnderReview->value])
            ->count();
    }
}
