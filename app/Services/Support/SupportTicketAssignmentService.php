<?php

namespace App\Services\Support;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketAssignmentService
{
    public function __construct(private readonly SupportTicketService $tickets) {}

    public function assign(SupportTicket $ticket, User $staff, User $actor): SupportTicket
    {
        return $this->tickets->assign($ticket, $staff, $actor);
    }
}
