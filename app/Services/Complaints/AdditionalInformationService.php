<?php

namespace App\Services\Complaints;

use App\Enums\AdditionalInformationRequestStatus;
use App\Enums\AdditionalInformationResponseStatus;
use App\Enums\ComplaintStatus;
use App\Enums\OfficialNotificationType;
use App\Models\AdditionalInformationRequest;
use App\Models\AdditionalInformationResponse;
use App\Models\Application;
use App\Models\Complaint;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OfficialNotificationService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdditionalInformationService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OfficialNotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Complaint $complaint, array $data, User $actor): AdditionalInformationRequest
    {
        if ($this->complaintStatus($complaint)?->isFinal() === true) {
            throw ValidationException::withMessages(['complaint' => 'Reclamações encerradas não aceitam pedidos complementares.']);
        }

        $notificationService = $this->notificationService;

        return DB::transaction(function () use ($complaint, $data, $actor, $notificationService) {
            $request = new AdditionalInformationRequest($data);
            $request->forceFill([
                'complaint_id' => $complaint->id,
                'application_id' => $complaint->application_id,
                'user_id' => $complaint->user_id,
                'request_number' => $this->generateRequestNumber(),
                'status' => AdditionalInformationRequestStatus::Open,
                'issued_by' => $actor->id,
                'issued_at' => now(),
            ])->save();

            $complaint->forceFill([
                'status' => ComplaintStatus::AwaitingCandidateResponse,
                'requires_additional_information' => true,
                'additional_information_requested_at' => now(),
                'additional_information_deadline_at' => $request->deadline_at,
            ])->save();

            $notificationService->createInternal(
                user: $this->requiredCandidate($complaint),
                type: OfficialNotificationType::AdditionalInformationRequested,
                subject: 'Pedido de informação complementar',
                body: 'Os serviços municipais solicitaram informação complementar para análise da sua reclamação. Responda dentro do prazo indicado.',
                notifiable: $request,
                application: $this->optionalApplication($complaint),
                actor: $actor,
            );

            $this->auditLogger->record(AuditEvents::CREATE, $request, 'complaints', 'additional_information_request_create', 'Pedido de informação complementar criado.');

            return $request->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function respond(AdditionalInformationRequest $request, array $data, User $candidate): AdditionalInformationResponse
    {
        if ($request->user_id !== $candidate->id || ! $this->requestStatusIsIn($request, [AdditionalInformationRequestStatus::Open, AdditionalInformationRequestStatus::Issued])) {
            throw ValidationException::withMessages(['additional_information_request' => 'Pedido complementar indisponível para resposta.']);
        }

        if (blank($data['response_text'] ?? null) && empty($data['document_submission_id'])) {
            throw ValidationException::withMessages(['response_text' => 'Indique uma resposta ou associe um documento.']);
        }

        if (! empty($data['document_submission_id'])) {
            $this->assertDocumentBelongsToCandidate((int) $data['document_submission_id'], $candidate, $request);
        }

        return DB::transaction(function () use ($request, $data, $candidate) {
            $response = new AdditionalInformationResponse([
                'response_text' => $data['response_text'] ?? null,
                'document_submission_id' => $data['document_submission_id'] ?? null,
            ]);
            $response->forceFill([
                'additional_information_request_id' => $request->id,
                'complaint_id' => $request->complaint_id,
                'application_id' => $request->application_id,
                'user_id' => $candidate->id,
                'status' => AdditionalInformationResponseStatus::Submitted,
                'submitted_at' => now(),
            ])->save();

            $request->forceFill([
                'status' => AdditionalInformationRequestStatus::Responded,
                'responded_at' => now(),
            ])->save();
            $this->requiredComplaint($request)->forceFill(['status' => ComplaintStatus::AdditionalInformationSubmitted])->save();

            $this->auditLogger->record(AuditEvents::CREATE, $response, 'complaints', 'additional_information_response_submit', 'Resposta a pedido complementar submetida.');

            return $response->refresh();
        });
    }

    public function close(AdditionalInformationRequest $request, User $actor): AdditionalInformationRequest
    {
        $request->forceFill(['status' => AdditionalInformationRequestStatus::Closed, 'closed_at' => now()])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $request, 'complaints', 'additional_information_request_close', 'Pedido complementar fechado.');

        return $request->refresh();
    }

    public function markOverdue(AdditionalInformationRequest $request, User $actor): AdditionalInformationRequest
    {
        $request->forceFill(['status' => AdditionalInformationRequestStatus::Overdue])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $request, 'complaints', 'additional_information_request_overdue', 'Pedido complementar marcado como vencido.');

        return $request->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reviewResponse(AdditionalInformationResponse $response, array $data, User $actor): AdditionalInformationResponse
    {
        $response->forceFill([
            'status' => AdditionalInformationResponseStatus::UnderReview,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_result' => $data['review_result'] ?? null,
            'review_notes' => $data['review_notes'] ?? null,
        ])->save();

        return $response->refresh();
    }

    private function assertDocumentBelongsToCandidate(int $documentId, User $candidate, AdditionalInformationRequest $request): void
    {
        $owned = DocumentSubmission::query()
            ->whereKey($documentId)
            ->where('user_id', $candidate->id)
            ->where(fn ($query) => $query->whereNull('application_id')->orWhere('application_id', $request->application_id))
            ->exists();

        if (! $owned) {
            throw ValidationException::withMessages(['document_submission_id' => 'Documento associado inválido.']);
        }
    }

    private function generateRequestNumber(): string
    {
        $next = AdditionalInformationRequest::withTrashed()->count() + 1;

        do {
            $number = 'INF-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (AdditionalInformationRequest::withTrashed()->where('request_number', $number)->exists());

        return $number;
    }

    private function complaintStatus(Complaint $complaint): ?ComplaintStatus
    {
        $status = $complaint->getAttribute('status');

        if ($status instanceof ComplaintStatus) {
            return $status;
        }

        return is_string($status) ? ComplaintStatus::tryFrom($status) : null;
    }

    /**
     * @param  list<AdditionalInformationRequestStatus>  $statuses
     */
    private function requestStatusIsIn(AdditionalInformationRequest $request, array $statuses): bool
    {
        $status = $this->requestStatus($request);

        return $status !== null && in_array($status, $statuses, true);
    }

    private function requestStatus(AdditionalInformationRequest $request): ?AdditionalInformationRequestStatus
    {
        $status = $request->getAttribute('status');

        if ($status instanceof AdditionalInformationRequestStatus) {
            return $status;
        }

        return is_string($status) ? AdditionalInformationRequestStatus::tryFrom($status) : null;
    }

    private function requiredCandidate(Complaint $complaint): User
    {
        $candidate = $complaint->candidate;

        if (! $candidate instanceof User) {
            throw ValidationException::withMessages(['candidate' => 'A reclamação não tem candidato associado.']);
        }

        return $candidate;
    }

    private function optionalApplication(Complaint $complaint): ?Application
    {
        $application = $complaint->application;

        return $application instanceof Application ? $application : null;
    }

    private function requiredComplaint(AdditionalInformationRequest $request): Complaint
    {
        $complaint = $request->complaint;

        if (! $complaint instanceof Complaint) {
            throw ValidationException::withMessages(['complaint' => 'O pedido complementar não tem reclamação associada.']);
        }

        return $complaint;
    }
}
