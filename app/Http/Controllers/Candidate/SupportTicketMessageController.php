<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\MessageVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTicketMessageRequest;
use App\Models\SupportTicket;
use App\Services\Support\SupportTicketMessageService;
use Illuminate\Http\RedirectResponse;

class SupportTicketMessageController extends Controller
{
    public function __construct(private readonly SupportTicketMessageService $messages) {}

    public function store(StoreSupportTicketMessageRequest $request, SupportTicket $supportTicket): RedirectResponse
    {
        $this->messages->addMessage(
            $supportTicket,
            $this->authenticatedUser($request),
            (string) $request->validated('message'),
            MessageVisibility::CandidateVisible,
        );

        return to_route('candidate.support-tickets.show', $supportTicket)->with('success', 'Mensagem enviada.');
    }
}
