<?php

namespace App\Services\Reporting\Indicators;

use App\Enums\ArrearStatus;
use App\Enums\RegularizationAgreementStatus;
use App\Enums\RentInstallmentStatus;
use App\Enums\RentReviewStatus;
use App\Models\Arrear;
use App\Models\RegularizationAgreement;
use App\Models\RentInstallment;
use App\Models\RentReview;
use App\Services\Reporting\ReportFilterService;
use Illuminate\Database\Eloquent\Builder;

class FinanceIndicatorsService
{
    public function __construct(private readonly ReportFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<RentInstallment>
     */
    private function installments(array $filters): Builder
    {
        return $this->filters->applyThroughContract(RentInstallment::query(), $filters, 'issue_date');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Arrear>
     */
    private function arrears(array $filters): Builder
    {
        return $this->filters->applyThroughContract(Arrear::query(), $filters, 'detected_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function totalIssuedRent(array $filters): float
    {
        return (float) $this->installments($filters)->sum('amount_due');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function totalPaidRent(array $filters): float
    {
        return (float) $this->installments($filters)->sum('amount_paid');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function totalOverdueRent(array $filters): float
    {
        return (float) $this->installments($filters)->where('status', RentInstallmentStatus::Overdue->value)->sum('amount_outstanding');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countContractsInArrears(array $filters): int
    {
        return $this->arrears($filters)->whereIn('status', [ArrearStatus::Open->value, ArrearStatus::Notified->value, ArrearStatus::UnderAgreement->value, ArrearStatus::PartiallyRegularized->value])->distinct('lease_contract_id')->count('lease_contract_id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function averageDaysOverdue(array $filters): float
    {
        return round((float) $this->arrears($filters)->avg('days_overdue'), 2);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countActiveAgreements(array $filters): int
    {
        return $this->filters->applyThroughContract(RegularizationAgreement::query(), $filters)->where('status', RegularizationAgreementStatus::Active->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countBreachedAgreements(array $filters): int
    {
        return $this->filters->applyThroughContract(RegularizationAgreement::query(), $filters)->where('status', RegularizationAgreementStatus::Breached->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countPendingRentReviews(array $filters): int
    {
        return $this->filters->applyThroughContract(RentReview::query(), $filters, 'requested_at')->whereIn('status', [RentReviewStatus::Requested->value, RentReviewStatus::UnderReview->value, RentReviewStatus::RequiresDocuments->value])->count();
    }
}
