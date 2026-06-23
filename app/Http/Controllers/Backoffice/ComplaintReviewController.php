<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintReview;
use App\Services\Complaints\ComplaintReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ComplaintReviewController extends Controller
{
    public function __construct(private readonly ComplaintReviewService $service) {}

    public function store(Request $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('create', ComplaintReview::class);
        $this->service->record($complaint, $this->authenticatedUser($request), $request->validate([
            'result' => ['nullable', 'string', 'max:100'],
            'summary' => ['nullable', 'string', 'max:3000'],
            'technical_notes' => ['nullable', 'string', 'max:3000'],
        ]));

        return back()->with('success', 'Análise registada.');
    }
}
