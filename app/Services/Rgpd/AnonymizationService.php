<?php

namespace App\Services\Rgpd;

use App\Enums\AnonymizationStatus;
use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\AnonymizationRequest;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Support\Str;
use RuntimeException;

class AnonymizationService
{
    public function __construct(private readonly AuditTrailService $audit) {}

    /**
     * @param array{
     *     data_subject_request_id: int|null,
     *     user_id: int|null,
     *     anonymization_type: string,
     *     reason: string,
     *     scope: array<int, string>
     * } $data
     */
    public function create(array $data, User $actor): AnonymizationRequest
    {
        $request = AnonymizationRequest::query()->create([
            'request_number' => 'ANON-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'data_subject_request_id' => $data['data_subject_request_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'status' => AnonymizationStatus::Draft,
            'anonymization_type' => $data['anonymization_type'],
            'reason' => $data['reason'],
            'scope' => $data['scope'],
        ]);

        $this->audit->record('anonymization_request.created', $request, AuditEventCategory::Rgpd, AuditEventSeverity::Warning, 'Pedido de anonimização criado.', actor: $actor);
        $this->audit->record('rgpd_anonymization_requested', $request, AuditEventCategory::Rgpd, AuditEventSeverity::Warning, 'Pedido RGPD de anonimização registado.', subject: $request->user, actor: $actor);

        return $request;
    }

    public function approve(AnonymizationRequest $request, User $actor): AnonymizationRequest
    {
        $request->forceFill(['status' => AnonymizationStatus::Approved, 'approved_by' => $actor->id, 'approved_at' => now()])->save();
        $this->audit->record('rgpd_anonymization_approved', $request, AuditEventCategory::Rgpd, AuditEventSeverity::Warning, 'Anonimização aprovada.', subject: $request->user, actor: $actor);

        return $request->refresh();
    }

    public function run(AnonymizationRequest $request, User $actor): AnonymizationRequest
    {
        if ($request->status !== AnonymizationStatus::Approved) {
            throw new RuntimeException('Anonimização exige aprovação prévia.');
        }

        $summary = ['message' => 'Anonimização executada de forma controlada.', 'scope' => $request->scope];
        if ($request->user && in_array('user.profile', $request->scope, true)) {
            $request->user->forceFill([
                'name' => 'Titular anonimizado '.$request->user->id,
                'email' => 'anon-'.$request->user->id.'@example.invalid',
            ])->save();
            $summary['user_profile_masked'] = true;
        }

        $request->forceFill([
            'status' => AnonymizationStatus::Completed,
            'executed_by' => $actor->id,
            'executed_at' => now(),
            'summary' => $summary,
        ])->save();

        $this->audit->record('anonymization_request.executed', $request, AuditEventCategory::Rgpd, AuditEventSeverity::Critical, 'Anonimização executada.', subject: $request->user, actor: $actor);
        $this->audit->record('rgpd_anonymization_executed', $request, AuditEventCategory::Rgpd, AuditEventSeverity::Critical, 'Anonimização RGPD executada.', subject: $request->user, actor: $actor);

        return $request->refresh();
    }
}
