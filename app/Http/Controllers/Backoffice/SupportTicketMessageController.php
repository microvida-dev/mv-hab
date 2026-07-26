<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\MessageVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTicketMessageRequest;
use App\Models\SupportTicket;
use App\Services\Support\SupportTicketMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SupportTicketMessageController extends Controller
{
    public function __construct(private readonly SupportTicketMessageService $messages) {}

    public function store(StoreSupportTicketMessageRequest $request, SupportTicket $supportTicket): RedirectResponse
    {
        Gate::authorize('messageBackoffice', $supportTicket);
        $visibility = MessageVisibility::tryFrom((string) $request->validated('visibility')) ?? MessageVisibility::CandidateVisible;
        $this->messages->addMessage(
            $supportTicket,
            $this->authenticatedUser($request),
            (string) $request->validated('message'),
            $visibility,
            backoffice: true,
        );

        return to_route('backoffice.support-tickets.show', $supportTicket)->with('success', 'Mensagem registada.');
    }
}
