<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AttendanceStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Present = 'present';
    case Absent = 'absent';
    case Justified = 'justified';
    case Late = 'late';
    case NotRequired = 'not_required';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Present => 'Presente',
            self::Absent => 'Ausente',
            self::Justified => 'Justificada',
            self::Late => 'Atrasado',
            self::NotRequired => 'Não exigida',
        };
    }
}
