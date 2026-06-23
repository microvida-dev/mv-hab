<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DefaultNoticeType: string
{
    use HasOptions;

    case PaymentDefault = 'payment_default';
    case RegularizationReminder = 'regularization_reminder';
    case AgreementBreach = 'agreement_breach';

    public function label(): string
    {
        return match ($this) {
            self::PaymentDefault => 'Incumprimento de pagamento',
            self::RegularizationReminder => 'Lembrete de regularização',
            self::AgreementBreach => 'Incumprimento de acordo',
        };
    }
}
