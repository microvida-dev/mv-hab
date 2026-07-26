<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\AnonymizationMode;
use App\Enums\RankingSnapshotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveProvisionalListRequest;
use App\Http\Requests\CloseComplaintPeriodRequest;
use App\Http\Requests\GenerateProvisionalListRequest;
use App\Http\Requests\OpenComplaintPeriodRequest;
use App\Http\Requests\PublishProvisionalListRequest;
use App\Models\ProvisionalList;
use App\Models\RankingSnapshot;
use App\Services\Lists\ProvisionalListService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProvisionalListController extends Controller
{
    public function __construct(
        private readonly ProvisionalListService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', ProvisionalList::class);
        $lists = $this->municipalScope
            ->provisionalLists(ProvisionalList::query(), $this->authenticatedUser($request))
            ->with(['program', 'contest', 'rankingSnapshot'])
            ->withCount('entries')
            ->latest()
            ->paginate(20);

        return view('backoffice.lists.provisional.index', compact('lists'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('generateAnyBackoffice', ProvisionalList::class);
        $snapshots = $this->municipalScope
            ->rankingSnapshots(RankingSnapshot::query(), $this->authenticatedUser($request))
            ->with(['program', 'contest', 'scoringRun'])
            ->whereIn('status', [RankingSnapshotStatus::Internal->value, RankingSnapshotStatus::Locked->value])
            ->latest('snapshot_number')
            ->get();

        return view('backoffice.lists.provisional.create', [
            'snapshots' => $snapshots,
            'anonymizationModes' => AnonymizationMode::options(),
        ]);
    }

    public function store(GenerateProvisionalListRequest $request): RedirectResponse
    {
        Gate::authorize('generateBackoffice', ProvisionalList::class);
        $list = $this->service->generateFromSnapshot($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.lists.provisional.show', $list)->with('success', 'Lista provisória gerada.');
    }

    public function show(ProvisionalList $provisionalList): View
    {
        Gate::authorize('viewBackoffice', $provisionalList);
        $provisionalList->load(['program', 'contest', 'rankingSnapshot', 'scoringRun', 'generatedBy', 'approvedBy', 'publishedBy', 'entries.application.user', 'complaints.decision', 'publications']);

        return view('backoffice.lists.provisional.show', compact('provisionalList'));
    }

    public function review(Request $request, ProvisionalList $provisionalList): RedirectResponse
    {
        Gate::authorize('reviewBackoffice', $provisionalList);
        $this->service->sendToReview($provisionalList, $this->authenticatedUser($request));

        return back()->with('success', 'Lista enviada para revisão.');
    }

    public function approve(ApproveProvisionalListRequest $request, ProvisionalList $provisionalList): RedirectResponse
    {
        Gate::authorize('approveBackoffice', $provisionalList);
        $this->service->approve($provisionalList, $this->authenticatedUser($request));

        return back()->with('success', 'Lista aprovada.');
    }

    public function publish(PublishProvisionalListRequest $request, ProvisionalList $provisionalList): RedirectResponse
    {
        Gate::authorize('publishBackoffice', $provisionalList);
        $this->service->publish($provisionalList, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Lista publicada de forma controlada.');
    }

    public function openComplaintPeriod(OpenComplaintPeriodRequest $request, ProvisionalList $provisionalList): RedirectResponse
    {
        Gate::authorize('openComplaintPeriodBackoffice', $provisionalList);
        $this->service->openComplaintPeriod($provisionalList, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Prazo de reclamação aberto.');
    }

    public function closeComplaintPeriod(CloseComplaintPeriodRequest $request, ProvisionalList $provisionalList): RedirectResponse
    {
        Gate::authorize('closeComplaintPeriodBackoffice', $provisionalList);
        $this->service->closeComplaintPeriod($provisionalList, $this->authenticatedUser($request));

        return back()->with('success', 'Prazo de reclamação fechado.');
    }

    public function cancel(Request $request, ProvisionalList $provisionalList): RedirectResponse
    {
        Gate::authorize('cancelBackoffice', $provisionalList);
        $this->service->cancel($provisionalList, $this->authenticatedUser($request));

        return back()->with('success', 'Lista cancelada.');
    }

    public function archive(Request $request, ProvisionalList $provisionalList): RedirectResponse
    {
        Gate::authorize('archiveBackoffice', $provisionalList);
        $this->service->archive($provisionalList, $this->authenticatedUser($request));

        return back()->with('success', 'Lista arquivada.');
    }
}
