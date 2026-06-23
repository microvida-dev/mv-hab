<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelLotteryDrawRequest;
use App\Http\Requests\RunLotteryDrawRequest;
use App\Http\Requests\StoreLotteryDrawRequest;
use App\Http\Requests\UpdateLotteryDrawRequest;
use App\Http\Requests\ValidateLotteryResultRequest;
use App\Models\AllocationRun;
use App\Models\LotteryDraw;
use App\Services\Lottery\LotteryDrawService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LotteryDrawController extends Controller
{
    public function __construct(private readonly LotteryDrawService $draws) {}

    public function index(): View
    {
        Gate::authorize('viewAny', LotteryDraw::class);

        return view('backoffice.lottery-draws.index', [
            'lotteryDraws' => LotteryDraw::query()->with(['contest', 'allocationRun'])->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', LotteryDraw::class);

        return view('backoffice.lottery-draws.create', [
            'allocationRuns' => AllocationRun::query()->with(['contest', 'definitiveList'])->latest()->get(),
        ]);
    }

    public function store(StoreLotteryDrawRequest $request): RedirectResponse
    {
        Gate::authorize('create', LotteryDraw::class);

        $draw = $this->draws->create($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.lottery-draws.show', $draw)->with('success', 'Sorteio criado.');
    }

    public function show(LotteryDraw $lotteryDraw): View
    {
        Gate::authorize('view', $lotteryDraw);

        $lotteryDraw->load(['contest', 'allocationRun', 'participants.candidate', 'results.candidate', 'convocations', 'attendances', 'winnerRegistrations']);

        return view('backoffice.lottery-draws.show', compact('lotteryDraw'));
    }

    public function edit(LotteryDraw $lotteryDraw): View
    {
        Gate::authorize('update', $lotteryDraw);

        return view('backoffice.lottery-draws.edit', compact('lotteryDraw'));
    }

    public function update(UpdateLotteryDrawRequest $request, LotteryDraw $lotteryDraw): RedirectResponse
    {
        Gate::authorize('update', $lotteryDraw);

        $this->draws->update($lotteryDraw, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.lottery-draws.show', $lotteryDraw)->with('success', 'Sorteio atualizado.');
    }

    public function run(RunLotteryDrawRequest $request, LotteryDraw $lotteryDraw): RedirectResponse
    {
        Gate::authorize('update', $lotteryDraw);

        $this->draws->run($lotteryDraw, $this->authenticatedUser($request), $request->validated('seed'));

        return to_route('backoffice.lottery-draws.show', $lotteryDraw)->with('success', 'Sorteio executado.');
    }

    public function validateResult(ValidateLotteryResultRequest $request, LotteryDraw $lotteryDraw): RedirectResponse
    {
        Gate::authorize('approve', $lotteryDraw);

        $this->draws->validateResult($lotteryDraw, $this->authenticatedUser($request));

        return to_route('backoffice.lottery-draws.show', $lotteryDraw)->with('success', 'Resultado validado.');
    }

    public function cancel(CancelLotteryDrawRequest $request, LotteryDraw $lotteryDraw): RedirectResponse
    {
        Gate::authorize('update', $lotteryDraw);

        $this->draws->cancel($lotteryDraw, $this->authenticatedUser($request), (string) $request->validated('reason'));

        return to_route('backoffice.lottery-draws.show', $lotteryDraw)->with('success', 'Sorteio cancelado.');
    }
}
