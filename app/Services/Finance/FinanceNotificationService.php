<?php

namespace App\Services\Finance;

use App\Enums\OfficialNotificationType;
use App\Models\AnnualDocumentUpdateRequest;
use App\Models\Arrear;
use App\Models\Contract;
use App\Models\DefaultNotice;
use App\Models\IncomeChangeDeclaration;
use App\Models\LeasePayment;
use App\Models\PaymentReceipt;
use App\Models\RegularizationAgreement;
use App\Models\RentReview;
use App\Models\RentSchedule;
use App\Models\User;
use App\Services\Notifications\OfficialNotificationService;
use Illuminate\Database\Eloquent\Model;

class FinanceNotificationService
{
    public function __construct(private readonly OfficialNotificationService $notifications) {}

    public function rentScheduleGenerated(RentSchedule $schedule, User $actor): void
    {
        $this->notify($schedule->tenant, OfficialNotificationType::RentScheduleGenerated, 'Plano de rendas disponível', 'Foi criado ou atualizado o plano de rendas do contrato.', $schedule, $actor);
    }

    public function leasePaymentRegistered(LeasePayment $payment, User $actor): void
    {
        $this->notify($payment->tenant, OfficialNotificationType::LeasePaymentRegistered, 'Pagamento registado', 'Foi registado internamente um pagamento associado ao contrato.', $payment, $actor);
    }

    public function paymentReceiptIssued(PaymentReceipt $receipt, User $actor): void
    {
        $this->notify($receipt->tenant, OfficialNotificationType::PaymentReceiptIssued, 'Comprovativo interno emitido', 'Está disponível um comprovativo interno do pagamento registado.', $receipt, $actor);
    }

    public function arrearDetected(Arrear $arrear, User $actor): void
    {
        $this->notify($arrear->tenant, OfficialNotificationType::ArrearDetected, 'Renda em atraso', 'Foi detetada uma prestação de renda em atraso.', $arrear, $actor);
    }

    public function defaultNoticeIssued(DefaultNotice $notice, User $actor): void
    {
        $this->notify($notice->tenant, OfficialNotificationType::DefaultNoticeIssued, 'Aviso de incumprimento emitido', 'Foi emitido um aviso interno de incumprimento para consulta na área reservada.', $notice, $actor);
    }

    public function regularizationAgreementCreated(RegularizationAgreement $agreement, User $actor): void
    {
        $this->notify($agreement->tenant, OfficialNotificationType::RegularizationAgreementCreated, 'Acordo de regularização criado', 'Foi criado um acordo de regularização de dívida para consulta.', $agreement, $actor);
    }

    public function rentReviewRequested(RentReview $review, User $actor): void
    {
        $this->notify($review->tenant, OfficialNotificationType::RentReviewRequested, 'Revisão de renda iniciada', 'Foi iniciado um processo de revisão de renda.', $review, $actor);
    }

    public function rentReviewApplied(RentReview $review, User $actor): void
    {
        $this->notify($review->tenant, OfficialNotificationType::RentReviewApplied, 'Revisão de renda aplicada', 'Foi aplicada uma revisão de renda ao contrato.', $review, $actor);
    }

    public function incomeChangeSubmitted(IncomeChangeDeclaration $declaration, User $actor): void
    {
        $this->notify($declaration->tenant, OfficialNotificationType::IncomeChangeSubmitted, 'Alteração de rendimentos submetida', 'A declaração de alteração de rendimentos foi registada para análise.', $declaration, $actor);
    }

    public function annualDocumentUpdateRequested(AnnualDocumentUpdateRequest $request, User $actor): void
    {
        $this->notify($request->tenant, OfficialNotificationType::AnnualDocumentUpdateRequested, 'Atualização documental anual solicitada', 'Foi solicitada a atualização anual de documentos do contrato.', $request, $actor);
    }

    private function notify(?User $user, OfficialNotificationType $type, string $subject, string $body, Model $notifiable, User $actor): void
    {
        if (! $user) {
            return;
        }

        $leaseContract = method_exists($notifiable, 'leaseContract')
            ? $notifiable->getRelationValue('leaseContract')
            : null;

        $this->notifications->createInternal(
            user: $user,
            type: $type,
            subject: $subject,
            body: $body,
            notifiable: $notifiable,
            application: $leaseContract instanceof Contract ? $leaseContract->application : null,
            actor: $actor,
        );
    }
}
