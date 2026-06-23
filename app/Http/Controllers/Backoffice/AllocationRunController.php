<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\AllocationMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelAllocationRequest;
use App\Http\Requests\RunAllocationRequest;
use App\Models\AllocationRuleSet;
use App\Models\AllocationRun;
use App\Models\DefinitiveList;
use App\Services\Allocation\AllocationEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AllocationRunController extends Controller
{
    public function __construct(
        private readonly AllocationEngine $engine,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', AllocationRun::class);

        return view('backoffice.allocation.runs.index', [
            'runs' => AllocationRun::query()
                ->with([
                    'contest',
                    'definitiveList',
                    'allocationRuleSet',
                ])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', AllocationRun::class);

        return view('backoffice.allocation.runs.create', [
            'definitiveLists' => DefinitiveList::query()
                ->published()
                ->with(['contest', 'program'])
                ->latest()
                ->get(),

            'ruleSets' => AllocationRuleSet::query()
                ->with(['contest', 'program'])
                ->latest()
                ->get(),

            'methods' => AllocationMethod::options(),
        ]);
    }

    public function store(
        RunAllocationRequest $request
    ): RedirectResponse {
        Gate::authorize('create', AllocationRun::class);

        $allocationRun = $this->engine->run(
            $request->validated(),
            $this->authenticatedUser($request),
        );

        return to_route(
            'backoffice.allocation.runs.show',
            $allocationRun,
        )->with(
            'success',
            'Execução de atribuição concluída.',
        );
    }

    public function show(
        AllocationRun $allocationRun
    ): View {
        Gate::authorize('view', $allocationRun);

        $allocationRun->load([
            'contest',
            'program',
            'definitiveList',
            'allocationRuleSet',
            'allocations.candidate',
            'allocations.housingUnit',
            'lotteryRun',
            'reserveList.entries.candidate',
            'reports',
        ]);

        return view(
            'backoffice.allocation.runs.show',
            compact('allocationRun'),
        );
    }

    public function run(
        AllocationRun $allocationRun
    ): RedirectResponse {
        Gate::authorize('update', $allocationRun);

        return to_route(
            'backoffice.allocation.runs.show',
            $allocationRun,
        )->with(
            'success',
            'Esta execução já está registada. Para nova atribuição, crie uma nova execução.',
        );
    }

    public function lock(
        AllocationRun $allocationRun
    ): RedirectResponse {
        Gate::authorize('update', $allocationRun);

        $this->engine->lock(
            $allocationRun,
            $this->currentUser(),
        );

        return back()->with(
            'success',
            'Execução de atribuição bloqueada.',
        );
    }

    public function cancel(
        CancelAllocationRequest $request,
        AllocationRun $allocationRun
    ): RedirectResponse {
        Gate::authorize('update', $allocationRun);

        $this->engine->cancel(
            $allocationRun,
            $this->authenticatedUser($request),
        );

        return back()->with(
            'success',
            'Execução de atribuição cancelada.',
        );
    }
}
