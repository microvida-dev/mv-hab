<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Services\Administrative\ApplicationIntakeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationIntakeController extends Controller
{
    public function __construct(private readonly ApplicationIntakeService $intakeService) {}

    public function index(): View
    {
        Gate::authorize('create', AdministrativeProcess::class);

        return view('backoffice.application-intake.index', [
            'applications' => $this->intakeService->pendingApplications()->paginate(20),
        ]);
    }

    public function createProcess(Request $request, Application $application): RedirectResponse
    {
        Gate::authorize('create', AdministrativeProcess::class);
        $process = $this->intakeService->createProcess($application, $this->authenticatedUser($request));

        return to_route('backoffice.administrative-processes.show', $process)
            ->with('success', 'Processo administrativo criado.');
    }

    public function createProcessesBatch(Request $request): RedirectResponse
    {
        Gate::authorize('create', AdministrativeProcess::class);
        $created = $this->intakeService->createProcessesBatch($this->authenticatedUser($request));

        return back()->with('success', $created->count().' processo(s) administrativo(s) criado(s).');
    }
}
