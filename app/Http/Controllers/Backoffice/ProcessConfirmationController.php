<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateProcessConfirmationRequest;
use App\Http\Requests\SendProcessConfirmationRequest;
use App\Models\Application;
use App\Models\ProcessConfirmation;
use App\Services\ProcessConfirmations\AutomaticProcessConfirmationService;
use App\Services\ProcessConfirmations\ProcessConfirmationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ProcessConfirmationController extends Controller
{
    public function __construct(
        private readonly AutomaticProcessConfirmationService $automatic,
        private readonly ProcessConfirmationService $confirmations,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ProcessConfirmation::class);
        $confirmations = ProcessConfirmation::query()->latest()->paginate(20);

        return view('backoffice.process-confirmations.index', compact('confirmations'));
    }

    public function show(ProcessConfirmation $processConfirmation): View
    {
        Gate::authorize('view', $processConfirmation);

        return view('backoffice.process-confirmations.show', compact('processConfirmation'));
    }

    public function generate(GenerateProcessConfirmationRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('create', ProcessConfirmation::class);
        $confirmation = $this->automatic->generate($application, $this->authenticatedUser($request), (bool) $request->boolean('force_regenerate'));

        return to_route('backoffice.process-confirmations.show', $confirmation)->with('success', 'Confirmação gerada.');
    }

    public function send(SendProcessConfirmationRequest $request, ProcessConfirmation $processConfirmation): RedirectResponse
    {
        Gate::authorize('send', $processConfirmation);
        $this->confirmations->markSent($processConfirmation, $this->authenticatedUser($request));

        return back()->with('success', 'Confirmação marcada como enviada.');
    }
}
