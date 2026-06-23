<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\AdhesionRegistration;
use App\Services\Candidate\RegistrationProgressService;
use App\Services\Documents\DocumentChecklistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly RegistrationProgressService $progressService,
        private readonly DocumentChecklistService $documentChecklistService,
    ) {}

    public function __invoke(Request $request): View
    {
        $registration = $this->authenticatedUser($request)
            ->adhesionRegistration()
            ->with([
                'statusHistories.changedBy',
                'household.members.incomeRecords',
                'household.incomeRecords',
                'currentHousingSituation',
                'documentSubmissions',
            ])
            ->first();
        $registration = $registration instanceof AdhesionRegistration ? $registration : null;

        $progress = $this->progressService->calculate($registration);
        $documentProgress = $registration
            ? $this->documentChecklistService->forRegistration($registration)['summary']
            : null;

        return view('candidate.dashboard', compact('registration', 'progress', 'documentProgress'));
    }
}
