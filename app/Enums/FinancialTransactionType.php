<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum FinancialTransactionType: string
{
    use HasOptions;

    case InstallmentIssued = 'installment_issued';
    case PaymentReceived = 'payment_received';
    case PaymentAllocated = 'payment_allocated';
    case PaymentReversed = 'payment_reversed';
    case Waiver = 'waiver';
    case RentReviewApplied = 'rent_review_applied';
    case ArrearDetected = 'arrear_detected';

    public function label(): string
    {
        return match ($this) {
            self::InstallmentIssued => 'Prestação emitida',
            self::PaymentReceived => 'Pagamento recebido',
            self::PaymentAllocated => 'Pagamento imputado',
            self::PaymentReversed => 'Pagamento estornado',
            self::Waiver => 'Dispensa',
            self::RentReviewApplied => 'Revisão de renda aplicada',
            self::ArrearDetected => 'Incumprimento detetado',
        };
    }
}
