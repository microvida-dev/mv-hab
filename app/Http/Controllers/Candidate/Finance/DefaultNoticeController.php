<?php

namespace App\Http\Controllers\Candidate\Finance;

use App\Http\Controllers\Controller;
use App\Models\DefaultNotice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class DefaultNoticeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DefaultNotice::class);
        $notices = DefaultNotice::query()
            ->where('user_id', $this->currentUser()->id)
            ->where('candidate_visible', true)
            ->latest()
            ->paginate(20);

        return view('candidate.finance.default-notices.index', compact('notices'));
    }

    public function show(DefaultNotice $defaultNotice): View
    {
        Gate::authorize('view', $defaultNotice);
        $defaultNotice->load(['arrear', 'tenantFinancialAccount']);

        return view('candidate.finance.default-notices.show', compact('defaultNotice'));
    }
}
