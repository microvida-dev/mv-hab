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
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($arrear, $actor, $data): DefaultNotice {
            $lockedArrear = Arrear::query()
                ->whereKey($arrear->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $notice = new DefaultNotice;
            $notice->forceFill([
                'arrear_id' => $lockedArrear->id,
                'tenant_financial_account_id' => $lockedArrear->tenant_financial_account_id,
                'lease_contract_id' => $lockedArrear->lease_contract_id,
                'user_id' => $lockedArrear->user_id,
                'notice_number' => $this->numbers->noticeNumber(),
                'status' => DefaultNoticeStatus::Draft,
                'notice_type' => DefaultNoticeType::from($data['notice_type'] ?? DefaultNoticeType::PaymentDefault->value),
                'subject' => $data['subject'],
                'body' => $data['body'],
                'amount_due' => $lockedArrear->outstanding_amount,
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'created_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::CREATE, $notice, 'finance', 'default_notice_create', 'Aviso de incumprimento criado.');

            return $notice->refresh();
        });
    }

    public function issue(DefaultNotice $notice, User $actor): DefaultNotice
    {
        return DB::transaction(function () use ($notice, $actor): DefaultNotice {
            $lockedNotice = DefaultNotice::query()
                ->whereKey($notice->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($this->noticeHasStatus($lockedNotice, DefaultNoticeStatus::Issued)) {
                return $lockedNotice;
            }

            $lockedNotice->forceFill([
                'status' => DefaultNoticeStatus::Issued,
                'issued_at' => now(),
                'issued_by' => $actor->id,
                'candidate_visible' => true,
            ])->save();

            $lockedNotice->arrear?->forceFill([
                'status' => ArrearStatus::Notified,
                'notified_at' => now(),
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::APPROVE, $lockedNotice, 'finance', 'default_notice_issue', 'Aviso de incumprimento emitido.');
            $this->notifications->defaultNoticeIssued($lockedNotice->refresh(), $actor);

            return $lockedNotice->refresh();
        });
    }

    public function cancel(DefaultNotice $notice, User $actor, string $reason): DefaultNotice
    {
        return DB::transaction(function () use ($notice, $actor, $reason): DefaultNotice {
            $lockedNotice = DefaultNotice::query()
                ->whereKey($notice->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($this->noticeHasStatus($lockedNotice, DefaultNoticeStatus::Cancelled)) {
                return $lockedNotice;
            }

            $lockedNotice->forceFill([
                'status' => DefaultNoticeStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'internal_notes' => trim(($lockedNotice->internal_notes ? $lockedNotice->internal_notes."\n" : '').'Cancelamento: '.$reason),
                'candidate_visible' => false,
            ])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $lockedNotice, 'finance', 'default_notice_cancel', 'Aviso de incumprimento cancelado.');

            return $lockedNotice->refresh();
        });
    }

    private function noticeHasStatus(DefaultNotice $notice, DefaultNoticeStatus $expected): bool
    {
        $status = $notice->getAttribute('status');

        return $status === $expected || $status === $expected->value;
    }
}
