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
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DefinitiveListController extends Controller
{
    public function __construct(private readonly DefinitiveListService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', DefinitiveList::class);
        $lists = DefinitiveList::query()->with(['program', 'contest', 'provisionalList'])->withCount('entries')->latest()->paginate(20);

        return view('backoffice.lists.definitive.index', compact('lists'));
    }

    public function create(): View
    {
        Gate::authorize('create', DefinitiveList::class);
        $provisionalLists = ProvisionalList::query()
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
        Gate::authorize('create', DefinitiveList::class);
        $provisionalList = ProvisionalList::query()->findOrFail($request->integer('provisional_list_id'));
        $list = $this->service->generateFromProvisional($provisionalList, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.lists.definitive.show', $list)->with('success', 'Lista definitiva gerada.');
    }

    public function show(DefinitiveList $definitiveList): View
    {
        Gate::authorize('view', $definitiveList);
        $definitiveList->load(['program', 'contest', 'provisionalList', 'entries.application.user', 'changeLogs', 'publications']);

        return view('backoffice.lists.definitive.show', compact('definitiveList'));
    }

    public function review(Request $request, DefinitiveList $definitiveList): RedirectResponse
    {
        Gate::authorize('update', $definitiveList);
        $this->service->sendToReview($definitiveList, $this->authenticatedUser($request));

        return back()->with('success', 'Lista definitiva enviada para revisão.');
    }

    public function approve(ApproveDefinitiveListRequest $request, DefinitiveList $definitiveList): RedirectResponse
    {
        Gate::authorize('approve', $definitiveList);
        $this->service->approve($definitiveList, $this->authenticatedUser($request));

        return back()->with('success', 'Lista definitiva aprovada.');
    }

    public function publish(PublishDefinitiveListRequest $request, DefinitiveList $definitiveList): RedirectResponse
    {
        Gate::authorize('publish', $definitiveList);
        $this->service->publish($definitiveList, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Lista definitiva publicada.');
    }

    public function lock(LockDefinitiveListRequest $request, DefinitiveList $definitiveList): RedirectResponse
    {
        Gate::authorize('publish', $definitiveList);
        $this->service->lock($definitiveList, $this->authenticatedUser($request));

        return back()->with('success', 'Lista definitiva bloqueada.');
    }

    public function archive(Request $request, DefinitiveList $definitiveList): RedirectResponse
    {
        Gate::authorize('update', $definitiveList);
        $this->service->archive($definitiveList, $this->authenticatedUser($request));

        return back()->with('success', 'Lista definitiva arquivada.');
    }
}
