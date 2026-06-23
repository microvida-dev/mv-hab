<?php

namespace App\Services\Reporting\Indicators;

use App\Enums\DocumentStatus;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Services\Reporting\ReportFilterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DocumentIndicatorsService
{
    public function __construct(private readonly ReportFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<DocumentSubmission>
     */
    private function query(array $filters): Builder
    {
        return $this->filters->applyThroughApplication(DocumentSubmission::query(), $filters, 'submitted_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countPendingDocuments(array $filters): int
    {
        return $this->query($filters)->whereIn('status', [DocumentStatus::Missing->value, DocumentStatus::Submitted->value, DocumentStatus::UnderReview->value])->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countSubmittedDocuments(array $filters): int
    {
        return $this->query($filters)->where('status', DocumentStatus::Submitted->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countRejectedDocuments(array $filters): int
    {
        return $this->query($filters)->where('status', DocumentStatus::Rejected->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countValidatedDocuments(array $filters): int
    {
        return $this->query($filters)->where('status', DocumentStatus::Validated->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countExpiredDocuments(array $filters): int
    {
        return $this->query($filters)->where('status', DocumentStatus::Expired->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function averageValidationTime(array $filters): float
    {
        $query = $this->query($filters)->whereNotNull('submitted_at')->whereNotNull('reviewed_at');
        $expression = DB::connection()->getDriverName() === 'sqlite'
            ? 'AVG(julianday(reviewed_at) - julianday(submitted_at))'
            : 'AVG(TIMESTAMPDIFF(SECOND, submitted_at, reviewed_at)) / 86400';

        return round((float) $query->selectRaw("$expression as average_days")->value('average_days'), 2);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countIncompleteApplications(array $filters): int
    {
        return $this->filters->applyApplication(Application::query(), $filters)
            ->whereHas('documentSubmissions', fn (Builder $query) => $query->whereIn('status', [
                DocumentStatus::Missing->value,
                DocumentStatus::Rejected->value,
                DocumentStatus::Expired->value,
            ]))->count();
    }
}
