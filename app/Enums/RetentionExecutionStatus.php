<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RetentionExecutionStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Simulation = 'simulation';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Reverted = 'reverted';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
