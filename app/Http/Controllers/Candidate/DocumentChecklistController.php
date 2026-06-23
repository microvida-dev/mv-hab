<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\AdhesionRegistration;
use App\Services\Documents\DocumentChecklistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentChecklistController extends Controller
{
    public function __construct(private readonly DocumentChecklistService $checklistService) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration()->first();

        if (! $registration instanceof AdhesionRegistration) {
            return to_route('candidate.registration.create')
                ->with('info', 'Crie o Registo de Adesão antes de submeter documentos.');
        }

        $checklist = $this->checklistService->forRegistration($registration);

        return view('candidate.documents.checklist', compact('registration', 'checklist'));
    }
}
