<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ChargeType: string
{
    use HasOptions;

    case Rent = 'rent';
    case Deposit = 'deposit';
    case Fee = 'fee';
    case Adjustment = 'adjustment';
    case Penalty = 'penalty';
    case MaintenanceCharge = 'maintenance_charge';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Rent => 'Renda',
            self::Deposit => 'Caução',
            self::Fee => 'Taxa',
            self::Adjustment => 'Acerto',
            self::Penalty => 'Penalização',
            self::MaintenanceCharge => 'Encargo de manutenção',
            self::Other => 'Outro',
        };
    }
}
