<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateDrawConvocationsRequest;
use App\Http\Requests\SendDrawConvocationRequest;
use App\Models\DrawConvocation;
use App\Models\LotteryDraw;
use App\Services\Convocations\AutomaticConvocationService;
use App\Services\Convocations\DrawConvocationService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DrawConvocationController extends Controller
{
    public function __construct(
        private readonly AutomaticConvocationService $automatic,
        private readonly DrawConvocationService $convocations,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', DrawConvocation::class);

        return view('backoffice.draw-convocations.index', [
            'convocations' => $this->municipalScope
                ->drawConvocations(
                    DrawConvocation::query(),
                    $this->authenticatedUser($request),
                )
                ->with(['lotteryDraw.contest', 'candidate'])
                ->latest()
                ->paginate(25),
        ]);
    }

    public function show(DrawConvocation $drawConvocation): View
    {
        Gate::authorize('viewBackoffice', $drawConvocation);

        $drawConvocation->load(['lotteryDraw.contest', 'candidate', 'application']);

        return view('backoffice.draw-convocations.show', compact('drawConvocation'));
    }

    public function generate(GenerateDrawConvocationsRequest $request, LotteryDraw $lotteryDraw): RedirectResponse
    {
        Gate::authorize('generateConvocationsBackoffice', $lotteryDraw);

        $this->automatic->forDraw($lotteryDraw, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.draw-convocations.index')->with('success', 'Convocatórias geradas.');
    }

    public function send(SendDrawConvocationRequest $request, DrawConvocation $drawConvocation): RedirectResponse
    {
        Gate::authorize('sendBackoffice', $drawConvocation);

        $this->convocations->send($drawConvocation, $this->authenticatedUser($request));

        return back()->with('success', 'Convocatória marcada como enviada.');
    }
}
