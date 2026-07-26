<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelFinanceRecordRequest;
use App\Http\Requests\StoreDefaultNoticeRequest;
use App\Models\Arrear;
use App\Models\DefaultNotice;
use App\Services\Finance\DefaultNoticeService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DefaultNoticeController extends Controller
{
    public function __construct(
        private readonly DefaultNoticeService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', DefaultNotice::class);
        $notices = $this->municipalScope
            ->defaultNotices(DefaultNotice::query(), $this->currentUser())
            ->with(['tenant', 'arrear'])
            ->latest()
            ->paginate(25);

        return view('backoffice.finance.default-notices.index', compact('notices'));
    }

    public function create(): View
    {
        Gate::authorize('createBackoffice', DefaultNotice::class);
        $arrears = $this->municipalScope
            ->arrears(Arrear::query(), $this->currentUser())
            ->with('tenant')
            ->latest()
            ->get();

        return view('backoffice.finance.default-notices.create', compact('arrears'));
    }

    public function store(StoreDefaultNoticeRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', DefaultNotice::class);
        $actor = $this->authenticatedUser($request);
        $arrear = $this->municipalScope
            ->arrears(Arrear::query(), $actor)
            ->findOrFail($request->integer('arrear_id'));
        $notice = $this->service->store($arrear, $actor, $request->validated());

        return redirect()->route('backoffice.finance.default-notices.show', $notice)->with('success', 'Aviso criado.');
    }

    public function show(DefaultNotice $defaultNotice): View
    {
        Gate::authorize('viewBackoffice', $defaultNotice);
        $defaultNotice->load(['tenant', 'arrear', 'tenantFinancialAccount']);

        return view('backoffice.finance.default-notices.show', compact('defaultNotice'));
    }

    public function issue(DefaultNotice $defaultNotice): RedirectResponse
    {
        Gate::authorize('issueBackoffice', $defaultNotice);
        $this->service->issue($defaultNotice, $this->currentUser());

        return back()->with('success', 'Aviso emitido e visível ao candidato.');
    }

    public function cancel(CancelFinanceRecordRequest $request, DefaultNotice $defaultNotice): RedirectResponse
    {
        Gate::authorize('cancelBackoffice', $defaultNotice);
        $this->service->cancel($defaultNotice, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Aviso cancelado.');
    }
}
