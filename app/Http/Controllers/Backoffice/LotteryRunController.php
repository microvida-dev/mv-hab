<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\LockLotteryRunRequest;
use App\Http\Requests\RunLotteryRequest;
use App\Models\AllocationRun;
use App\Models\LotteryRun;
use App\Services\Allocation\LotteryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LotteryRunController extends Controller
{
    public function __construct(
        private readonly LotteryService $lotteryService
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', LotteryRun::class);

        return view('backoffice.allocation.lotteries.index', [
            'lotteries' => LotteryRun::query()
                ->with(['contest', 'allocationRun'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', LotteryRun::class);

        return view('backoffice.allocation.lotteries.create', [
            'runs' => AllocationRun::query()
                ->with(['contest', 'definitiveList'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(
        RunLotteryRequest $request
    ): RedirectResponse {
        Gate::authorize('create', LotteryRun::class);

        /** @var array{allocation_run_id:int} $data */
        $data = $request->validated();

        /** @var AllocationRun $run */
        $run = AllocationRun::query()->findOrFail(
            (int) $data['allocation_run_id']
        );

        $result = $this->lotteryService->run(
            $run,
            $this->authenticatedUser($request),
            $data
        );

        return to_route(
            'backoffice.allocation.lotteries.show',
            $result->lotteryRun
        )->with(
            'success',
            'Sorteio executado.'
        );
    }

    public function show(
        LotteryRun $lotteryRun
    ): View {
        Gate::authorize('view', $lotteryRun);

        $lotteryRun->load([
            'allocationRun',
            'contest',
            'definitiveList',
            'participants.candidate',
            'drawResults.candidate',
        ]);

        return view(
            'backoffice.allocation.lotteries.show',
            compact('lotteryRun')
        );
    }

    public function run(
        LotteryRun $lotteryRun
    ): RedirectResponse {
        Gate::authorize('update', $lotteryRun);

        return to_route(
            'backoffice.allocation.lotteries.show',
            $lotteryRun
        )->with(
            'success',
            'O sorteio já está registado. Crie nova execução para novo sorteio.'
        );
    }

    public function lock(
        LockLotteryRunRequest $request,
        LotteryRun $lotteryRun
    ): RedirectResponse {
        Gate::authorize('update', $lotteryRun);

        $this->lotteryService->lock(
            $lotteryRun,
            $this->authenticatedUser($request)
        );

        return back()->with(
            'success',
            'Sorteio bloqueado.'
        );
    }

    public function audit(
        LotteryRun $lotteryRun
    ): View {
        Gate::authorize('audit', $lotteryRun);

        $lotteryRun->load([
            'participants.candidate',
            'drawResults.candidate',
        ]);

        return view(
            'backoffice.allocation.lotteries.audit',
            compact('lotteryRun')
        );
    }
}
