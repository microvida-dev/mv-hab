<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelFinanceRecordRequest;
use App\Http\Requests\StoreDefaultNoticeRequest;
use App\Models\Arrear;
use App\Models\DefaultNotice;
use App\Services\Finance\DefaultNoticeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DefaultNoticeController extends Controller
{
    public function __construct(private readonly DefaultNoticeService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', DefaultNotice::class);
        $notices = DefaultNotice::query()->with(['tenant', 'arrear'])->latest()->paginate(25);

        return view('backoffice.finance.default-notices.index', compact('notices'));
    }

    public function create(): View
    {
        Gate::authorize('create', DefaultNotice::class);
        $arrears = Arrear::query()->with('tenant')->latest()->get();

        return view('backoffice.finance.default-notices.create', compact('arrears'));
    }

    public function store(StoreDefaultNoticeRequest $request): RedirectResponse
    {
        Gate::authorize('create', DefaultNotice::class);
        $arrear = Arrear::query()->findOrFail($request->integer('arrear_id'));
        $notice = $this->service->store($arrear, $this->authenticatedUser($request), $request->validated());

        return redirect()->route('backoffice.finance.default-notices.show', $notice)->with('success', 'Aviso criado.');
    }

    public function show(DefaultNotice $defaultNotice): View
    {
        Gate::authorize('view', $defaultNotice);
        $defaultNotice->load(['tenant', 'arrear', 'tenantFinancialAccount']);

        return view('backoffice.finance.default-notices.show', compact('defaultNotice'));
    }

    public function issue(DefaultNotice $defaultNotice): RedirectResponse
    {
        Gate::authorize('update', $defaultNotice);
        $this->service->issue($defaultNotice, $this->currentUser());

        return back()->with('success', 'Aviso emitido e visível ao candidato.');
    }

    public function cancel(CancelFinanceRecordRequest $request, DefaultNotice $defaultNotice): RedirectResponse
    {
        Gate::authorize('update', $defaultNotice);
        $this->service->cancel($defaultNotice, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Aviso cancelado.');
    }
}
