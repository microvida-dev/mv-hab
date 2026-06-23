<?php

namespace App\Services\Complaints;

use App\Enums\ComplaintStatus;
use App\Enums\OfficialNotificationType;
use App\Models\Application;
use App\Models\Complaint;
use App\Models\DocumentSubmission;
use App\Models\ProvisionalList;
use App\Models\ProvisionalListEntry;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OfficialNotificationService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComplaintService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OfficialNotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $candidate): Complaint
    {
        $list = ProvisionalList::query()->whereKey((int) $data['provisional_list_id'])->firstOrFail();
        $application = Application::query()->whereKey((int) $data['application_id'])->firstOrFail();
        $entry = $this->resolveEntry($list, $application, $candidate, $data['provisional_list_entry_id'] ?? null);

        if ($application->user_id !== $candidate->id || $entry->user_id !== $candidate->id) {
            throw ValidationException::withMessages(['application_id' => 'Só pode reclamar sobre a sua própria candidatura.']);
        }

        if (! $list->isComplaintPeriodOpen()) {
            throw ValidationException::withMessages(['provisional_list_id' => 'A lista não está em período ativo de reclamação.']);
        }

        $openComplaint = Complaint::query()
            ->where('provisional_list_entry_id', $entry->id)
            ->whereNotIn('status', [ComplaintStatus::Withdrawn->value, ComplaintStatus::Cancelled->value, ComplaintStatus::Closed->value])
            ->exists();

        if ($openComplaint) {
            throw ValidationException::withMessages(['provisional_list_entry_id' => 'Já existe uma reclamação para esta entrada.']);
        }

        return DB::transaction(function () use ($data, $candidate, $list, $application, $entry) {
            $complaint = new Complaint([
                'subject' => $data['subject'],
                'grounds' => $data['grounds'],
                'requested_outcome' => $data['requested_outcome'] ?? null,
            ]);
            $complaint->forceFill([
                'provisional_list_id' => $list->id,
                'provisional_list_entry_id' => $entry->id,
                'application_id' => $application->id,
                'user_id' => $candidate->id,
                'complaint_number' => $this->generateComplaintNumber(),
                'status' => ComplaintStatus::Draft,
                'candidate_visible' => true,
            ])->save();

            foreach ($this->attachmentsData($data) as $attachment) {
                if (! empty($attachment['document_submission_id'])) {
                    $this->assertDocumentBelongsToCandidate((int) $attachment['document_submission_id'], $candidate, $application);
                }

                $complaint->attachments()->create([
                    'document_submission_id' => $attachment['document_submission_id'] ?? null,
                    'description' => $attachment['description'] ?? null,
                    'uploaded_by' => $candidate->id,
                ]);
            }

            $this->auditLogger->record(AuditEvents::CREATE, $complaint, 'complaints', 'complaint_create', 'Reclamação criada em rascunho.');

            $complaint->refresh();

            return $complaint->load(['attachments']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Complaint $complaint, array $data, User $candidate): Complaint
    {
        if ($complaint->user_id !== $candidate->id || $this->complaintStatus($complaint) !== ComplaintStatus::Draft) {
            throw ValidationException::withMessages(['complaint' => 'Apenas rascunhos próprios podem ser editados.']);
        }

        $complaint->fill([
            'subject' => $data['subject'],
            'grounds' => $data['grounds'],
            'requested_outcome' => $data['requested_outcome'] ?? null,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $complaint, 'complaints', 'complaint_update', 'Reclamação em rascunho atualizada.');

        return $complaint->refresh();
    }

    public function submit(Complaint $complaint, User $candidate): Complaint
    {
        if ($complaint->user_id !== $candidate->id || $this->complaintStatus($complaint) !== ComplaintStatus::Draft) {
            throw ValidationException::withMessages(['complaint' => 'Apenas rascunhos próprios podem ser submetidos.']);
        }

        if (! $this->requiredProvisionalList($complaint)->isComplaintPeriodOpen()) {
            throw ValidationException::withMessages(['complaint' => 'O prazo de reclamação não está ativo.']);
        }

        $complaint->forceFill([
            'status' => ComplaintStatus::Submitted,
            'submitted_at' => now(),
        ])->save();

        $this->notificationService->createInternal(
            user: $candidate,
            type: OfficialNotificationType::ComplaintReceived,
            subject: 'Reclamação submetida',
            body: 'A sua reclamação foi submetida com sucesso e ficará disponível para análise pelos serviços municipais.',
            notifiable: $complaint,
            application: $this->optionalApplication($complaint),
            actor: $candidate,
        );

        $this->auditLogger->record(AuditEvents::CREATE, $complaint, 'complaints', 'complaint_submit', 'Reclamação submetida pelo candidato.');

        return $complaint->refresh();
    }

    public function withdraw(Complaint $complaint, User $candidate): Complaint
    {
        if ($complaint->user_id !== $candidate->id || ! $this->complaintStatusIsIn($complaint, [ComplaintStatus::Draft, ComplaintStatus::Submitted, ComplaintStatus::Received])) {
            throw ValidationException::withMessages(['complaint' => 'A reclamação não pode ser desistida neste estado.']);
        }

        $complaint->forceFill(['status' => ComplaintStatus::Withdrawn, 'withdrawn_at' => now(), 'closed_at' => now()])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $complaint, 'complaints', 'complaint_withdraw', 'Reclamação desistida pelo candidato.');

        return $complaint->refresh();
    }

    public function markReceived(Complaint $complaint, User $actor): Complaint
    {
        if ($this->complaintStatus($complaint) !== ComplaintStatus::Submitted) {
            throw ValidationException::withMessages(['complaint' => 'Apenas reclamações submetidas podem ser marcadas como recebidas.']);
        }

        $complaint->forceFill(['status' => ComplaintStatus::Received, 'received_at' => now()])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $complaint, 'complaints', 'complaint_received', 'Reclamação marcada como recebida.');

        return $complaint->refresh();
    }

    public function assign(Complaint $complaint, User $assignee, User $actor): Complaint
    {
        $complaint->forceFill(['assigned_to' => $assignee->id, 'assigned_at' => now()])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $complaint, 'complaints', 'complaint_assign', 'Reclamação atribuída a técnico.', metadata: ['assignee_id' => $assignee->id]);

        return $complaint->refresh();
    }

    public function startReview(Complaint $complaint, User $actor): Complaint
    {
        if (! $this->complaintStatusIsIn($complaint, [ComplaintStatus::Submitted, ComplaintStatus::Received, ComplaintStatus::AdditionalInformationSubmitted])) {
            throw ValidationException::withMessages(['complaint' => 'A reclamação não está pronta para análise.']);
        }

        $complaint->forceFill(['status' => ComplaintStatus::UnderReview, 'review_started_at' => now()])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $complaint, 'complaints', 'complaint_start_review', 'Análise de reclamação iniciada.');

        return $complaint->refresh();
    }

    public function close(Complaint $complaint, User $actor): Complaint
    {
        if (! $this->complaintStatus($complaint)?->isFinal()) {
            throw ValidationException::withMessages(['complaint' => 'A reclamação precisa de decisão final antes de fechar.']);
        }

        $complaint->forceFill(['status' => ComplaintStatus::Closed, 'closed_at' => now()])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $complaint, 'complaints', 'complaint_close', 'Reclamação fechada.');

        return $complaint->refresh();
    }

    private function resolveEntry(ProvisionalList $list, Application $application, User $candidate, ?int $entryId): ProvisionalListEntry
    {
        $query = $list->entries()->where('application_id', $application->id)->where('user_id', $candidate->id);

        if ($entryId !== null) {
            $query->whereKey($entryId);
        }

        return $query->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function attachmentsData(array $data): array
    {
        $attachments = $data['attachments'] ?? [];

        return is_array($attachments) ? array_values(array_filter($attachments, 'is_array')) : [];
    }

    private function assertDocumentBelongsToCandidate(int $documentId, User $candidate, Application $application): void
    {
        $owned = DocumentSubmission::query()
            ->whereKey($documentId)
            ->where('user_id', $candidate->id)
            ->where(fn ($query) => $query->whereNull('application_id')->orWhere('application_id', $application->id))
            ->exists();

        if (! $owned) {
            throw ValidationException::withMessages(['attachments' => 'Documento associado inválido para esta reclamação.']);
        }
    }

    private function generateComplaintNumber(): string
    {
        $next = Complaint::withTrashed()->count() + 1;

        do {
            $number = 'REC-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (Complaint::withTrashed()->where('complaint_number', $number)->exists());

        return $number;
    }

    /**
     * @param  list<ComplaintStatus>  $statuses
     */
    private function complaintStatusIsIn(Complaint $complaint, array $statuses): bool
    {
        $status = $this->complaintStatus($complaint);

        return $status !== null && in_array($status, $statuses, true);
    }

    private function complaintStatus(Complaint $complaint): ?ComplaintStatus
    {
        $status = $complaint->getAttribute('status');

        if ($status instanceof ComplaintStatus) {
            return $status;
        }

        return is_string($status) ? ComplaintStatus::tryFrom($status) : null;
    }

    private function requiredProvisionalList(Complaint $complaint): ProvisionalList
    {
        $list = $complaint->provisionalList;

        if (! $list instanceof ProvisionalList) {
            throw ValidationException::withMessages(['provisional_list' => 'A reclamação não tem lista provisória associada.']);
        }

        return $list;
    }

    private function optionalApplication(Complaint $complaint): ?Application
    {
        $application = $complaint->application;

        return $application instanceof Application ? $application : null;
    }
}
