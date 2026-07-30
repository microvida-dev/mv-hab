<?php

namespace App\Services\Administrative;

use App\Enums\ApplicationReviewStatus;
use App\Enums\DocumentStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\Contest;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class ApplicationReviewWorkspaceService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @return Collection<int, Contest>
     */
    public function contests(User $user): Collection
    {
        return $this->municipalScope
            ->contests(Contest::query(), $user)
            ->withCount([
                'administrativeProcesses as processes_count',
            ])
            ->orderByDesc('closes_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array{
     *     search: string,
     *     process_status: string,
     *     review_status: string,
     *     assigned_to: int|null,
     *     readiness: string
     * }  $filters
     * @return LengthAwarePaginator<int, AdministrativeProcess>
     */
    public function processes(
        Contest $contest,
        User $user,
        array $filters,
    ): LengthAwarePaginator {
        $query = $this->baseProcessQuery($contest, $user)
            ->with([
                'application' => function (Relation $relation): void {
                    /** @var Builder<Application> $applications */
                    $applications = $relation->getQuery();

                    $applications->with([
                        'user',
                        'documentSubmissions.documentType',
                        'documentSubmissions.requiredDocument',
                    ])
                        ->withCount([
                            'documentSubmissions as documents_total',
                            'documentSubmissions as documents_submitted' => fn (
                                Builder $documents,
                            ): Builder => $documents->where(
                                'status',
                                DocumentStatus::Submitted->value,
                            ),
                            'documentSubmissions as documents_under_review' => fn (
                                Builder $documents,
                            ): Builder => $documents->where(
                                'status',
                                DocumentStatus::UnderReview->value,
                            ),
                            'documentSubmissions as documents_validated' => fn (
                                Builder $documents,
                            ): Builder => $documents->where(
                                'status',
                                DocumentStatus::Validated->value,
                            ),
                            'documentSubmissions as documents_rejected' => fn (
                                Builder $documents,
                            ): Builder => $documents->where(
                                'status',
                                DocumentStatus::Rejected->value,
                            ),
                            'documentSubmissions as documents_expired' => fn (
                                Builder $documents,
                            ): Builder => $documents->where(
                                'status',
                                DocumentStatus::Expired->value,
                            ),
                        ]);
                },
                'candidate',
                'assignedTo',
                'latestDocumentalReview.readyForClosureBy',
            ]);

        if ($filters['search'] !== '') {
            $like = '%'.$filters['search'].'%';

            $query->where(function (Builder $processes) use (
                $like,
            ): void {
                $processes
                    ->where('process_number', 'like', $like)
                    ->orWhereHas(
                        'application',
                        fn (Builder $applications): Builder => $applications
                            ->where(
                                'application_number',
                                'like',
                                $like,
                            ),
                    )
                    ->orWhereHas(
                        'candidate',
                        fn (Builder $candidates): Builder => $candidates
                            ->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like),
                    );
            });
        }

        if ($filters['process_status'] !== '') {
            $query->where(
                'status',
                $filters['process_status'],
            );
        }

        if ($filters['review_status'] !== '') {
            $query->whereHas(
                'latestDocumentalReview',
                fn (Builder $reviews): Builder => $reviews->where(
                    'status',
                    $filters['review_status'],
                ),
            );
        }

        if ($filters['assigned_to'] !== null) {
            $query->where(
                'assigned_to',
                $filters['assigned_to'],
            );
        }

        if ($filters['readiness'] === 'ready') {
            $query->whereHas(
                'latestDocumentalReview',
                fn (Builder $reviews): Builder => $reviews->where(
                    'status',
                    ApplicationReviewStatus::ReadyForClosure->value,
                ),
            );
        }

        if ($filters['readiness'] === 'not_ready') {
            $query->where(function (Builder $processes): void {
                $processes
                    ->whereDoesntHave('latestDocumentalReview')
                    ->orWhereHas(
                        'latestDocumentalReview',
                        fn (Builder $reviews): Builder => $reviews->where(
                            'status',
                            '!=',
                            ApplicationReviewStatus::ReadyForClosure->value,
                        ),
                    );
            });
        }

        return $query
            ->orderByRaw('assigned_to is null desc')
            ->orderBy('assigned_to')
            ->orderBy('process_number')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @return array{
     *     total: int,
     *     unassigned: int,
     *     in_progress: int,
     *     ready: int,
     *     pending_documents: int
     * }
     */
    public function statistics(
        Contest $contest,
        User $user,
    ): array {
        $base = $this->baseProcessQuery($contest, $user);
        $applicationIds = (clone $base)
            ->select('application_id');

        return [
            'total' => (clone $base)->count(),
            'unassigned' => (clone $base)
                ->whereNull('assigned_to')
                ->count(),
            'in_progress' => (clone $base)
                ->whereHas(
                    'latestDocumentalReview',
                    fn (Builder $reviews): Builder => $reviews->where(
                        'status',
                        ApplicationReviewStatus::InProgress->value,
                    ),
                )
                ->count(),
            'ready' => (clone $base)
                ->whereHas(
                    'latestDocumentalReview',
                    fn (Builder $reviews): Builder => $reviews->where(
                        'status',
                        ApplicationReviewStatus::ReadyForClosure->value,
                    ),
                )
                ->count(),
            'pending_documents' => $this->municipalScope
                ->documentSubmissions(
                    DocumentSubmission::query(),
                    $user,
                )
                ->whereIn('application_id', $applicationIds)
                ->whereIn('status', [
                    DocumentStatus::Submitted->value,
                    DocumentStatus::UnderReview->value,
                    DocumentStatus::Rejected->value,
                    DocumentStatus::Expired->value,
                    DocumentStatus::Missing->value,
                ])
                ->count(),
        ];
    }

    /**
     * @return Builder<AdministrativeProcess>
     */
    private function baseProcessQuery(
        Contest $contest,
        User $user,
    ): Builder {
        return $this->municipalScope
            ->administrativeProcesses(
                AdministrativeProcess::query(),
                $user,
            )
            ->where('contest_id', $contest->id);
    }
}
