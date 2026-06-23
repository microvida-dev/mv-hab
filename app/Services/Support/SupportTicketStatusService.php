<?php

namespace App\Services\Support;

use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketStatusService
{
    public function __construct(private readonly SupportTicketService $tickets) {}

    public function update(SupportTicket $ticket, TicketStatus $status, User $actor, ?string $message = null): SupportTicket
    {
        return $this->tickets->updateStatus($ticket, $status, $actor, $message);
    }
}
