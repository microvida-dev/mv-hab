<?php

namespace App\Services\DocumentIntelligence;

use App\Enums\DocumentAiValidationSeverity;
use App\Models\Application;
use App\Models\DocumentAiValidationRun;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DocumentValidationDashboardService
{
    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, DocumentAiValidationRun>
     */
    public function runs(array $filters, User $user): LengthAwarePaginator
    {
        return $this->municipalScope
            ->documentAiValidationRuns(DocumentAiValidationRun::query(), $user)
            ->with(['application.user', 'application.contest'])
            ->withCount('validations')
            ->when($filters['status'] ?? null, fn ($query, string $value) => $query->where('status', $value))
            ->when(array_key_exists('requires_review', $filters), fn ($query) => $query->where('requires_manual_review', (bool) $filters['requires_review']))
            ->when($filters['severity'] ?? null, fn ($query, string $value) => $query->whereHas('validations', fn ($validations) => $validations->where('severity', $value)))
            ->when($filters['group'] ?? null, fn ($query, string $value) => $query->whereHas('validations', fn ($validations) => $validations->where('validation_group', $value)))
            ->when($filters['application'] ?? null, function ($query, string $value): void {
                $query->whereHas('application', function ($applications) use ($value): void {
                    $applications->where('application_number', 'like', "%{$value}%")
                        ->orWhere('public_id', 'like', "%{$value}%")
                        ->orWhereHas('user', fn ($users) => $users->where('name', 'like', "%{$value}%"));
                });
            })
            ->when($filters['from'] ?? null, fn ($query, string $value) => $query->whereDate('created_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($query, string $value) => $query->whereDate('created_at', '<=', $value))
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }

    public function latestRunFor(Application $application, User $user): ?DocumentAiValidationRun
    {
        return $this->municipalScope
            ->documentAiValidationRuns(
                DocumentAiValidationRun::query()->where('application_id', $application->id),
                $user,
            )
            ->with(['validations.analysis.documentSubmission.documentType'])
            ->latest()
            ->first();
    }

    /**
     * @return array<string, int>
     */
    public function totals(User $user): array
    {
        return [
            'runs' => $this->municipalScope
                ->documentAiValidationRuns(DocumentAiValidationRun::query(), $user)
                ->count(),
            'requires_review' => $this->municipalScope
                ->documentAiValidationRuns(DocumentAiValidationRun::query(), $user)
                ->requiresReview()
                ->count(),
            'critical' => $this->municipalScope
                ->documentAiValidationRuns(DocumentAiValidationRun::query(), $user)
                ->where('critical_count', '>', 0)
                ->count(),
            'medium' => $this->municipalScope
                ->documentAiValidationRuns(DocumentAiValidationRun::query(), $user)
                ->where('medium_count', '>', 0)
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function summary(DocumentAiValidationRun $run): array
    {
        return [
            'total' => (int) $run->total_checks,
            'matches' => (int) $run->matches_count,
            'critical' => (int) $run->critical_count,
            'medium' => (int) $run->medium_count,
            'light' => (int) $run->light_count,
            'inconclusive' => (int) $run->inconclusive_count,
            'none' => $run->validations
                ->filter(fn ($validation): bool => $validation->severity === DocumentAiValidationSeverity::None)
                ->count(),
        ];
    }
}
