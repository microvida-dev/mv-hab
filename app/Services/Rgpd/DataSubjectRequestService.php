<?php

namespace App\Services\Rgpd;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\DataSubjectRequestActionType;
use App\Enums\DataSubjectRequestStatus;
use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Support\Str;

class DataSubjectRequestService
{
    public function __construct(private readonly AuditTrailService $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $subject, ?User $actor = null): DataSubjectRequest
    {
        $performer = $actor ?? $subject;

        $request = DataSubjectRequest::query()->create([
            'request_number' => 'RGPD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'user_id' => $subject?->id,
            'requester_name' => $data['requester_name'] ?? $subject?->name,
            'requester_email' => $data['requester_email'] ?? $subject?->email,
            'requester_phone' => $data['requester_phone'] ?? null,
            'request_type' => $data['request_type'],
            'status' => DataSubjectRequestStatus::Submitted,
            'description' => $data['description'],
            'received_at' => now(),
            'due_at' => now()->addDays(30),
            'created_by' => $performer?->id,
        ]);

        $request->actions()->create([
            'action_type' => DataSubjectRequestActionType::DataSearch,
            'status' => 'pending',
            'description' => 'Pedido RGPD recebido para análise municipal.',
            'performed_by' => $performer?->id,
            'performed_at' => now(),
        ]);

        $this->audit->record('data_subject_request.created', $request, AuditEventCategory::Rgpd, AuditEventSeverity::Notice, 'Pedido RGPD criado.', subject: $subject, actor: $performer);

        return $request->refresh();
    }

    public function assign(DataSubjectRequest $request, User $assignee, User $actor): DataSubjectRequest
    {
        $request->forceFill(['assigned_to' => $assignee->id, 'status' => DataSubjectRequestStatus::UnderReview])->save();
        $this->action($request, 'identity_verification', 'Pedido atribuído para análise.', $actor);

        return $request->refresh();
    }

    public function complete(DataSubjectRequest $request, User $actor, string $summary): DataSubjectRequest
    {
        $request->forceFill(['status' => DataSubjectRequestStatus::Completed, 'completed_at' => now(), 'closed_by' => $actor->id, 'internal_notes' => $summary])->save();
        $this->action($request, 'closure', $summary, $actor);

        $subject = $request->user;
        $this->audit->record('data_subject_request.completed', $request, AuditEventCategory::Rgpd, AuditEventSeverity::Notice, 'Pedido RGPD concluído.', subject: $subject instanceof User ? $subject : null, actor: $actor);

        return $request->refresh();
    }

    public function reject(DataSubjectRequest $request, User $actor, string $reason): DataSubjectRequest
    {
        $request->forceFill(['status' => DataSubjectRequestStatus::Rejected, 'rejected_at' => now(), 'rejection_reason' => $reason, 'closed_by' => $actor->id])->save();
        $this->action($request, 'closure', $reason, $actor);

        return $request->refresh();
    }

    public function action(DataSubjectRequest $request, string $type, string $description, User $actor): void
    {
        $request->actions()->create([
            'action_type' => $type,
            'status' => 'completed',
            'description' => $description,
            'performed_by' => $actor->id,
            'performed_at' => now(),
        ]);
    }
}
