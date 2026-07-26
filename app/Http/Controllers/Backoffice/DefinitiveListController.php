<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\AnonymizationMode;
use App\Enums\ProvisionalListStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveDefinitiveListRequest;
use App\Http\Requests\GenerateDefinitiveListRequest;
use App\Http\Requests\LockDefinitiveListRequest;
use App\Http\Requests\PublishDefinitiveListRequest;
use App\Models\DefinitiveList;
use App\Models\ProvisionalList;
use App\Services\Lists\DefinitiveListService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DefinitiveListController extends Controller
{
    public function __construct(
        private readonly DefinitiveListService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', DefinitiveList::class);
        $lists = $this->municipalScope
            ->definitiveLists(DefinitiveList::query(), $this->authenticatedUser($request))
            ->with(['program', 'contest', 'provisionalList'])
            ->withCount('entries')
            ->latest()
            ->paginate(20);

        return view('backoffice.lists.definitive.index', compact('lists'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('generateAnyBackoffice', DefinitiveList::class);
        $provisionalLists = $this->municipalScope
            ->provisionalLists(ProvisionalList::query(), $this->authenticatedUser($request))
            ->with(['contest', 'program'])
            ->where('status', ProvisionalListStatus::ComplaintPeriodClosed->value)
            ->doesntHave('definitiveList')
            ->latest()
            ->get();

        return view('backoffice.lists.definitive.create', [
            'provisionalLists' => $provisionalLists,
            'anonymizationModes' => AnonymizationMode::options(),
        ]);
    }

    public function store(GenerateDefinitiveListRequest $request): RedirectResponse
    {
        Gate::authorize('generateBackoffice', DefinitiveList::class);
        $provisionalList = $this->municipalScope
            ->provisionalLists(ProvisionalList::query(), $this->authenticatedUser($request))
            ->findOrFail($request->integer('provisional_list_id'));
        $list = $this->service->generateFromProvisional($provisionalList, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.lists.definitive.show', $list)->with('success', 'Lista definitiva gerada.');
    }

    public function show(DefinitiveList $definitiveList): View
    {
        Gate::authorize('viewBackoffice', $definitiveList);
        $definitiveList->load(['program', 'contest', 'provisionalList', 'entries.application.user', 'changeLogs', 'publications']);

        return view('backoffice.lists.definitive.show', compact('definitiveList'));
    }

    public function review(Request $request, DefinitiveList $definitiveList): RedirectResponse
    {
        Gate::authorize('reviewBackoffice', $definitiveList);
        $this->service->sendToReview($definitiveList, $this->authenticatedUser($request));

        return back()->with('success', 'Lista definitiva enviada para revisão.');
    }

    public function approve(ApproveDefinitiveListRequest $request, DefinitiveList $definitiveList): RedirectResponse
    {
        Gate::authorize('approveBackoffice', $definitiveList);
        $this->service->approve($definitiveList, $this->authenticatedUser($request));

        return back()->with('success', 'Lista definitiva aprovada.');
    }

    public function publish(PublishDefinitiveListRequest $request, DefinitiveList $definitiveList): RedirectResponse
    {
        Gate::authorize('publishBackoffice', $definitiveList);
        $this->service->publish($definitiveList, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Lista definitiva publicada.');
    }

    public function lock(LockDefinitiveListRequest $request, DefinitiveList $definitiveList): RedirectResponse
    {
        Gate::authorize('lockBackoffice', $definitiveList);
        $this->service->lock($definitiveList, $this->authenticatedUser($request));

        return back()->with('success', 'Lista definitiva bloqueada.');
    }

    public function archive(Request $request, DefinitiveList $definitiveList): RedirectResponse
    {
        Gate::authorize('archiveBackoffice', $definitiveList);
        $this->service->archive($definitiveList, $this->authenticatedUser($request));

        return back()->with('success', 'Lista definitiva arquivada.');
    }
}
