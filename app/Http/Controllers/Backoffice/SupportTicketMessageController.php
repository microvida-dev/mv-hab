<?php

namespace App\Http\Controllers\Backoffice;

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
        $visibility = MessageVisibility::tryFrom((string) $request->validated('visibility')) ?? MessageVisibility::CandidateVisible;
        $this->messages->addMessage($supportTicket, $this->authenticatedUser($request), (string) $request->validated('message'), $visibility);

        return to_route('backoffice.support-tickets.show', $supportTicket)->with('success', 'Mensagem registada.');
    }
}
