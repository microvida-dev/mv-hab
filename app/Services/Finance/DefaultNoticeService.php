<?php

namespace App\Services\Finance;

use App\Enums\ArrearStatus;
use App\Enums\DefaultNoticeStatus;
use App\Enums\DefaultNoticeType;
use App\Models\Arrear;
use App\Models\DefaultNotice;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class DefaultNoticeService
{
    public function __construct(
        private readonly FinanceNumberService $numbers,
        private readonly FinanceNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(Arrear $arrear, User $actor, array $data): DefaultNotice
    {
        $notice = new DefaultNotice;
        $notice->forceFill([
            'arrear_id' => $arrear->id,
            'tenant_financial_account_id' => $arrear->tenant_financial_account_id,
            'lease_contract_id' => $arrear->lease_contract_id,
            'user_id' => $arrear->user_id,
            'notice_number' => $this->numbers->noticeNumber(),
            'status' => DefaultNoticeStatus::Draft,
            'notice_type' => DefaultNoticeType::from($data['notice_type'] ?? DefaultNoticeType::PaymentDefault->value),
            'subject' => $data['subject'],
            'body' => $data['body'],
            'amount_due' => $data['amount_due'] ?? $arrear->outstanding_amount,
            'due_date' => $data['due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'created_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $notice, 'finance', 'default_notice_create', 'Aviso de incumprimento criado.');

        return $notice->refresh();
    }

    public function issue(DefaultNotice $notice, User $actor): DefaultNotice
    {
        $notice->forceFill([
            'status' => DefaultNoticeStatus::Issued,
            'issued_at' => now(),
            'issued_by' => $actor->id,
            'candidate_visible' => true,
        ])->save();

        $notice->arrear?->forceFill([
            'status' => ArrearStatus::Notified,
            'notified_at' => now(),
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::APPROVE, $notice, 'finance', 'default_notice_issue', 'Aviso de incumprimento emitido.');
        $this->notifications->defaultNoticeIssued($notice->refresh(), $actor);

        return $notice->refresh();
    }

    public function cancel(DefaultNotice $notice, User $actor, string $reason): DefaultNotice
    {
        $notice->forceFill([
            'status' => DefaultNoticeStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
            'internal_notes' => trim(($notice->internal_notes ? $notice->internal_notes."\n" : '').'Cancelamento: '.$reason),
            'candidate_visible' => false,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $notice, 'finance', 'default_notice_cancel', 'Aviso de incumprimento cancelado.');

        return $notice->refresh();
    }
}
