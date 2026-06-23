<?php

namespace App\Http\Controllers\Candidate\Finance;

use App\Http\Controllers\Controller;
use App\Models\RegularizationAgreement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class RegularizationAgreementController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', RegularizationAgreement::class);
        $agreements = RegularizationAgreement::query()->where('user_id', $this->currentUser()->id)->latest()->paginate(20);

        return view('candidate.finance.regularization-agreements.index', compact('agreements'));
    }

    public function show(RegularizationAgreement $regularizationAgreement): View
    {
        Gate::authorize('view', $regularizationAgreement);
        $regularizationAgreement->load(['arrears', 'installments']);

        return view('candidate.finance.regularization-agreements.show', compact('regularizationAgreement'));
    }
}
