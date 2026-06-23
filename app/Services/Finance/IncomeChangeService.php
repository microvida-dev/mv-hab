<?php

namespace App\Services\Finance;

use App\Enums\IncomeChangeStatus;
use App\Enums\IncomeChangeType;
use App\Models\IncomeChangeDeclaration;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class IncomeChangeService
{
    public function __construct(
        private readonly RentReviewService $rentReviews,
        private readonly FinanceNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(TenantFinancialAccount $account, User $actor, array $data): IncomeChangeDeclaration
    {
        if ($account->user_id !== $actor->id && $actor->hasRole('candidate')) {
            throw ValidationException::withMessages(['account' => 'Não pode declarar alterações para esta conta.']);
        }

        $declaration = new IncomeChangeDeclaration;
        $declaration->forceFill([
            'tenant_financial_account_id' => $account->id,
            'lease_contract_id' => $account->lease_contract_id,
            'user_id' => $account->user_id,
            'household_id' => $account->household_id,
            'status' => IncomeChangeStatus::Draft,
            'change_type' => IncomeChangeType::from($data['change_type'] ?? IncomeChangeType::IncomeChange->value),
            'changed_at' => $data['changed_at'] ?? today(),
            'monthly_income_before' => $data['monthly_income_before'] ?? null,
            'monthly_income_after' => $data['monthly_income_after'] ?? null,
            'declared_reason' => $data['declared_reason'],
            'candidate_notes' => $data['candidate_notes'] ?? null,
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $declaration, 'finance', 'income_change_create', 'Declaração de alteração de rendimentos criada.');

        return $declaration->refresh();
    }

    public function submit(IncomeChangeDeclaration $declaration, User $actor): IncomeChangeDeclaration
    {
        if ($declaration->user_id !== $actor->id && $actor->hasRole('candidate')) {
            throw ValidationException::withMessages(['declaration' => 'Não pode submeter esta declaração.']);
        }

        $declaration->forceFill([
            'status' => IncomeChangeStatus::Submitted,
            'submitted_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $declaration, 'finance', 'income_change_submit', 'Declaração de alteração de rendimentos submetida.');
        $this->notifications->incomeChangeSubmitted($declaration->refresh(), $actor);

        return $declaration->refresh();
    }

    public function accept(IncomeChangeDeclaration $declaration, User $actor, ?string $notes = null): IncomeChangeDeclaration
    {
        $review = $this->rentReviews->createFromIncomeChange($declaration, $actor);

        $declaration->forceFill([
            'status' => IncomeChangeStatus::Accepted,
            'reviewed_at' => now(),
            'reviewed_by' => $actor->id,
            'review_notes' => $notes,
            'rent_review_id' => $review->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::APPROVE, $declaration, 'finance', 'income_change_accept', 'Declaração de alteração de rendimentos aceite.');

        return $declaration->refresh();
    }

    public function reject(IncomeChangeDeclaration $declaration, User $actor, string $reason): IncomeChangeDeclaration
    {
        $declaration->forceFill([
            'status' => IncomeChangeStatus::Rejected,
            'reviewed_at' => now(),
            'reviewed_by' => $actor->id,
            'rejection_reason' => $reason,
        ])->save();

        $this->auditLogger->record(AuditEvents::REJECT, $declaration, 'finance', 'income_change_reject', 'Declaração de alteração de rendimentos rejeitada.');

        return $declaration->refresh();
    }
}
