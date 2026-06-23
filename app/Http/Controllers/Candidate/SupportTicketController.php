<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\Application;
use App\Models\SupportTicket;
use App\Services\Support\SupportTicketService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SupportTicketController extends Controller
{
    public function __construct(private readonly SupportTicketService $tickets) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SupportTicket::class);

        return view('candidate.support-tickets.index', [
            'tickets' => SupportTicket::query()
                ->forUser($this->authenticatedUser($request))
                ->with(['application.contest', 'contest', 'housingUnit'])
                ->latest('last_message_at')
                ->paginate(10),
            'notice' => 'As mensagens trocadas neste canal ficam associadas ao seu processo e podem ser consultadas pelos serviços municipais para efeitos de acompanhamento, resposta e auditoria.',
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', SupportTicket::class);

        return view('candidate.support-tickets.create', [
            'categories' => TicketCategory::options(),
            'priorities' => TicketPriority::options(),
            'applications' => Application::query()->forUser($this->authenticatedUser($request))->with('contest')->latest()->get(),
            'notice' => 'As mensagens trocadas neste canal ficam associadas ao seu processo e podem ser consultadas pelos serviços municipais para efeitos de acompanhamento, resposta e auditoria.',
        ]);
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $ticket = $this->tickets->create($this->authenticatedUser($request), $request->validated());

        return to_route('candidate.support-tickets.show', $ticket)->with('success', 'Pedido de apoio criado.');
    }

    public function show(SupportTicket $supportTicket): View
    {
        Gate::authorize('view', $supportTicket);
        $supportTicket->load(['application.contest', 'contest', 'housingUnit', 'messages.sender', 'attachments']);

        return view('candidate.support-tickets.show', [
            'ticket' => $supportTicket,
            'messages' => $supportTicket->messages()->candidateVisible()->with('sender')->get(),
            'notice' => 'As mensagens trocadas neste canal ficam associadas ao seu processo e podem ser consultadas pelos serviços municipais para efeitos de acompanhamento, resposta e auditoria.',
        ]);
    }
}
