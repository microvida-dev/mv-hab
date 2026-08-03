<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewStatus;
use App\Enums\BulkApplicationReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkApplicationReviewRequest;
use App\Http\Requests\ReviewWorkspaceFilterRequest;
use App\Models\AdministrativeProcess;
use App\Models\Contest;
use App\Models\User;
use App\Services\Administrative\ApplicationReviewWorkspaceService;
use App\Services\Administrative\BulkApplicationReviewService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationReviewWorkspaceController extends Controller
{
    public function __construct(
        private readonly ApplicationReviewWorkspaceService $workspaceService,
        private readonly BulkApplicationReviewService $bulkReviewService,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AdministrativeProcess::class);
        $user = $this->authenticatedUser($request);

        return view(
            'backoffice.application-review-workspace.index',
            [
                'contests' => $this->workspaceService->contests($user),
            ],
        );
    }

    public function show(
        ReviewWorkspaceFilterRequest $request,
        Contest $contest,
    ): View {
        $user = $this->authenticatedUser($request);
        $this->authorizeContest($user, $contest);
        $filters = $request->filters();

        $analysts = $this->municipalScope
            ->users(User::query(), $user)
            ->where('status', 'active')
            ->whereDoesntHave(
                'roles',
                fn (Builder $roles): Builder => $roles->whereIn(
                    'name',
                    ['candidate', 'auditor'],
                ),
            )
            ->with('roles.permissions')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(static fn (User $analyst): bool => $analyst
                ->hasPermissionTo('administrative_processes', 'view')
                && $analyst->hasPermissionTo('documents', 'view'))
            ->values();

        return view(
            'backoffice.application-review-workspace.show',
            [
                'contest' => $contest,
                'processes' => $this->workspaceService->processes(
                    $contest,
                    $user,
                    $filters,
                ),
                'statistics' => $this->workspaceService->statistics(
                    $contest,
                    $user,
                ),
                'filters' => $filters,
                'analysts' => $analysts,
                'processStatuses' => AdministrativeProcessStatus::options(),
                'reviewStatuses' => ApplicationReviewStatus::options(),
                'bulkActions' => BulkApplicationReviewAction::options(),
            ],
        );
    }

    public function preview(
        BulkApplicationReviewRequest $request,
        Contest $contest,
    ): View {
        $user = $this->authenticatedUser($request);
        $this->authorizeContest($user, $contest);

        return view(
            'backoffice.application-review-workspace.preview',
            [
                'contest' => $contest,
                'preview' => $this->bulkReviewService->preview(
                    $contest,
                    $user,
                    $request->payload(),
                ),
            ],
        );
    }

    public function apply(
        BulkApplicationReviewRequest $request,
        Contest $contest,
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);
        $this->authorizeContest($user, $contest);

        $result = $this->bulkReviewService->apply(
            $contest,
            $user,
            $request->payload(),
        );

        return to_route(
            'backoffice.application-review-workspace.show',
            $contest,
        )->with(
            'success',
            sprintf(
                '%s concluída: %d processo(s) e %d documento(s) abrangidos.',
                $result['action'],
                $result['processes'],
                $result['documents'],
            ),
        );
    }

    private function authorizeContest(
        User $user,
        Contest $contest,
    ): void {
        Gate::forUser($user)->authorize(
            'viewAny',
            AdministrativeProcess::class,
        );

        abort_unless(
            $this->municipalScope->ownsContest($user, $contest),
            403,
        );
    }
}
