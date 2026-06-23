<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveProcedureMinuteRequest;
use App\Http\Requests\GenerateProcedureMinuteRequest;
use App\Models\ProcedureMinute;
use App\Models\ProcedureTemplate;
use App\Services\ProcedureMinutes\ProcedureMinuteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcedureMinuteController extends Controller
{
    public function __construct(private readonly ProcedureMinuteService $minutes) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ProcedureMinute::class);
        $minutes = ProcedureMinute::query()->latest()->paginate(20);
        $templates = ProcedureTemplate::query()->where('type', 'procedure_minute')->latest()->get();

        return view('backoffice.procedure-minutes.index', compact('minutes', 'templates'));
    }

    public function generate(GenerateProcedureMinuteRequest $request): RedirectResponse
    {
        Gate::authorize('create', ProcedureMinute::class);
        $minute = $this->minutes->generate($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.procedure-minutes.show', $minute)->with('success', 'Ata gerada.');
    }

    public function show(ProcedureMinute $procedureMinute): View
    {
        Gate::authorize('view', $procedureMinute);

        return view('backoffice.procedure-minutes.show', compact('procedureMinute'));
    }

    public function approve(ApproveProcedureMinuteRequest $request, ProcedureMinute $procedureMinute): RedirectResponse
    {
        Gate::authorize('approve', $procedureMinute);
        $this->minutes->approve($procedureMinute, $this->authenticatedUser($request));

        return back()->with('success', 'Ata aprovada.');
    }

    public function download(ProcedureMinute $procedureMinute): StreamedResponse
    {
        Gate::authorize('download', $procedureMinute);
        abort_if($procedureMinute->file_path === null || ! Storage::disk('local')->exists($procedureMinute->file_path), 404);

        return Storage::disk('local')->download($procedureMinute->file_path, $procedureMinute->minute_number.'.html');
    }
}
