<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Applications\ApplicationReceiptService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ApplicationReceiptController extends Controller
{
    public function __construct(private readonly ApplicationReceiptService $receiptService) {}

    public function show(Application $application): View
    {
        Gate::authorize('viewReceipt', $application);

        return view('candidate.applications.receipt', $this->receiptService->data($application));
    }

    public function print(Application $application): View
    {
        Gate::authorize('viewReceipt', $application);

        return view('candidate.applications.print', $this->receiptService->data($application));
    }
}
