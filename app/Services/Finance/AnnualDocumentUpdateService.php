<?php

namespace App\Services\Finance;

use App\Enums\AnnualDocumentSubmissionStatus;
use App\Enums\AnnualDocumentUpdateStatus;
use App\Models\AnnualDocumentUpdateRequest;
use App\Models\AnnualDocumentUpdateSubmission;
use App\Models\DocumentSubmission;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnnualDocumentUpdateService
{
    public function __construct(
        private readonly FinanceNumberService $numbers,
        private readonly FinanceNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function request(TenantFinancialAccount $account, User $actor, array $data): AnnualDocumentUpdateRequest
    {
        $request = new AnnualDocumentUpdateRequest;
        $request->forceFill([
            'tenant_financial_account_id' => $account->id,
            'lease_contract_id' => $account->lease_contract_id,
            'user_id' => $account->user_id,
            'household_id' => $account->household_id,
            'request_number' => $this->numbers->annualUpdateNumber(),
            'status' => AnnualDocumentUpdateStatus::Requested,
            'reference_year' => $data['reference_year'] ?? now()->year,
            'due_date' => $data['due_date'] ?? now()->addDays(30)->toDateString(),
            'requested_at' => now(),
            'required_document_types' => $data['required_document_types'] ?? null,
            'notes' => $data['notes'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'requested_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $request, 'finance', 'annual_document_update_request', 'Atualização documental anual solicitada.');
        $this->notifications->annualDocumentUpdateRequested($request->refresh(), $actor);

        return $request->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(AnnualDocumentUpdateRequest $request, User $actor, array $data): AnnualDocumentUpdateRequest
    {
        if ($request->user_id !== $actor->id && $actor->hasRole('candidate')) {
            throw ValidationException::withMessages(['request' => 'Não pode submeter documentos para este pedido.']);
        }

        return DB::transaction(function () use ($request, $data) {
            $documentIds = $data['document_submission_ids'] ?? [];

            foreach ($documentIds as $documentId) {
                $document = DocumentSubmission::query()
                    ->where('id', $documentId)
                    ->where('user_id', $request->user_id)
                    ->firstOrFail();

                AnnualDocumentUpdateSubmission::query()->create([
                    'annual_document_update_request_id' => $request->id,
                    'document_submission_id' => $document->id,
                    'user_id' => $request->user_id,
                    'status' => AnnualDocumentSubmissionStatus::Submitted,
                    'submitted_at' => now(),
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            $request->forceFill([
                'status' => AnnualDocumentUpdateStatus::Submitted,
                'submitted_at' => now(),
            ])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $request, 'finance', 'annual_document_update_submit', 'Atualização documental anual submetida.');

            return $request->refresh();
        });
    }

    public function accept(AnnualDocumentUpdateRequest $request, User $actor, ?string $notes = null): AnnualDocumentUpdateRequest
    {
        $request->submissions()->update([
            'status' => AnnualDocumentSubmissionStatus::Accepted,
            'reviewed_at' => now(),
            'reviewed_by' => $actor->id,
        ]);

        $request->forceFill([
            'status' => AnnualDocumentUpdateStatus::Accepted,
            'reviewed_at' => now(),
            'reviewed_by' => $actor->id,
            'notes' => $notes ?? $request->notes,
        ])->save();

        $this->auditLogger->record(AuditEvents::APPROVE, $request, 'finance', 'annual_document_update_accept', 'Atualização documental anual aceite.');

        return $request->refresh();
    }

    public function reject(AnnualDocumentUpdateRequest $request, User $actor, string $reason): AnnualDocumentUpdateRequest
    {
        $request->submissions()->update([
            'status' => AnnualDocumentSubmissionStatus::Rejected,
            'reviewed_at' => now(),
            'reviewed_by' => $actor->id,
            'rejection_reason' => $reason,
        ]);

        $request->forceFill([
            'status' => AnnualDocumentUpdateStatus::Rejected,
            'reviewed_at' => now(),
            'reviewed_by' => $actor->id,
            'internal_notes' => trim(($request->internal_notes ? $request->internal_notes."\n" : '').'Rejeição: '.$reason),
        ])->save();

        $this->auditLogger->record(AuditEvents::REJECT, $request, 'finance', 'annual_document_update_reject', 'Atualização documental anual rejeitada.');

        return $request->refresh();
    }
}
