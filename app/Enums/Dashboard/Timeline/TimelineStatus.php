<?php

namespace App\Enums\Dashboard\Timeline;

enum TimelineStatus: string
{
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}
