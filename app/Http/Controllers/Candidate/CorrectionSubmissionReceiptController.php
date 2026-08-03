<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class CorrectionSubmissionReceiptController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function show(
        CorrectionRequest $correctionRequest,
    ): View {
        Gate::authorize('view', $correctionRequest);

        $receipt = $correctionRequest
            ->submissionReceipt()
            ->firstOrFail();

        abort_unless(
            (int) $receipt->user_id === (int) auth()->id(),
            403,
        );

        $this->audit->record(
            event: AuditEvents::ACCESS,
            auditable: $receipt,
            module: 'administrative_processes',
            action: 'correction_submission_receipt_view',
            description: 'Recibo de submissão do aperfeiçoamento consultado.',
            metadata: [
                'correction_request_id' => $correctionRequest->id,
                'snapshot_hash' => $receipt->snapshot_hash,
            ],
        );

        return view(
            'candidate.correction-requests.receipt',
            [
                'correctionRequest' => $correctionRequest,
                'receipt' => $receipt,
                'snapshot' => $receipt->snapshot_payload,
            ],
        );
    }
}
