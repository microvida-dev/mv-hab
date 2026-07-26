<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveProcedureMinuteRequest;
use App\Http\Requests\GenerateProcedureMinuteRequest;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ProcedureMinute;
use App\Models\ProcedureTemplate;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\ProcedureMinutes\ProcedureMinuteService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcedureMinuteController extends Controller
{
    public function __construct(
        private readonly ProcedureMinuteService $minutes,
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly AuditLogger $audit,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', ProcedureMinute::class);
        $user = $this->currentUser();
        $minutes = $this->municipalScope
            ->procedureMinutes(ProcedureMinute::query(), $user)
            ->with(['contest', 'application.user', 'template'])
            ->latest()
            ->paginate(20);
        $templates = ProcedureTemplate::query()->where('type', 'procedure_minute')->latest()->get();
        $contests = $this->municipalScope
            ->contests(Contest::query(), $user)
            ->latest()
            ->limit(100)
            ->get(['id', 'code', 'title', 'status']);
        $applications = $this->municipalScope
            ->applications(Application::query(), $user)
            ->with('user')
            ->latest()
            ->limit(100)
            ->get(['id', 'application_number', 'user_id', 'contest_id', 'status']);

        return view('backoffice.procedure-minutes.index', compact('minutes', 'templates', 'contests', 'applications'));
    }

    public function generate(GenerateProcedureMinuteRequest $request): RedirectResponse
    {
        Gate::authorize('create', ProcedureMinute::class);
        $minute = $this->minutes->generate($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.procedure-minutes.show', $minute)->with('success', 'Ata gerada.');
    }

    public function show(ProcedureMinute $procedureMinute): View
    {
        Gate::authorize('viewBackoffice', $procedureMinute);
        $procedureMinute->loadMissing(['contest', 'application.user', 'template']);

        return view('backoffice.procedure-minutes.show', compact('procedureMinute'));
    }

    public function approve(ApproveProcedureMinuteRequest $request, ProcedureMinute $procedureMinute): RedirectResponse
    {
        Gate::authorize('approveBackoffice', $procedureMinute);
        $this->minutes->approve($procedureMinute, $this->authenticatedUser($request));

        return back()->with('success', 'Ata aprovada.');
    }

    public function download(ProcedureMinute $procedureMinute): StreamedResponse
    {
        Gate::authorize('downloadBackoffice', $procedureMinute);
        $path = ltrim((string) $procedureMinute->file_path, '/');
        abort_if(
            $path === ''
                || str_contains($path, '..')
                || ! Storage::disk('local')->exists($path),
            404,
        );
        $this->audit->record(
            AuditEvents::ACCESS,
            $procedureMinute,
            'documents',
            'procedure_minute_downloaded',
            'Ata do procedimento descarregada.',
        );

        return Storage::disk('local')->download(
            $path,
            basename($procedureMinute->minute_number.'.html'),
        );
    }

    public function destroy(ProcedureMinute $procedureMinute): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $procedureMinute);

        $this->minutes->delete($procedureMinute, $this->currentUser());

        return to_route('backoffice.procedure-minutes.index')
            ->with('success', 'Ata eliminada.');
    }
}
