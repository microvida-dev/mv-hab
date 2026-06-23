<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignSupportTicketRequest;
use App\Http\Requests\UpdateSupportTicketStatusRequest;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\CandidateExperience\CandidateSupportDashboardService;
use App\Services\Support\SupportTicketAssignmentService;
use App\Services\Support\SupportTicketStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SupportTicketController extends Controller
{
    public function __construct(
        private readonly SupportTicketAssignmentService $assignments,
        private readonly SupportTicketStatusService $statuses,
        private readonly CandidateSupportDashboardService $dashboard,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', SupportTicket::class);

        return view('backoffice.support-tickets.index', [
            'tickets' => SupportTicket::query()->with(['user', 'application.contest', 'assignee'])->latest('last_message_at')->paginate(20),
            'indicators' => $this->dashboard->indicators(),
        ]);
    }

    public function show(SupportTicket $supportTicket): View
    {
        Gate::authorize('view', $supportTicket);
        $supportTicket->load(['user', 'application.contest', 'contest', 'housingUnit', 'assignee', 'messages.sender', 'attachments']);

        return view('backoffice.support-tickets.show', [
            'ticket' => $supportTicket,
            'statuses' => TicketStatus::options(),
            'staffUsers' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function assign(AssignSupportTicketRequest $request, SupportTicket $supportTicket): RedirectResponse
    {
        $staff = User::query()->findOrFail($request->integer('assigned_to'));
        $ticket = $this->assignments->assign($supportTicket, $staff, $this->authenticatedUser($request));

        return to_route('backoffice.support-tickets.show', $ticket)->with('success', 'Ticket atribuído.');
    }

    public function updateStatus(UpdateSupportTicketStatusRequest $request, SupportTicket $supportTicket): RedirectResponse
    {
        $ticket = $this->statuses->update(
            $supportTicket,
            TicketStatus::from((string) $request->validated('status')),
            $this->authenticatedUser($request),
            $request->validated('message'),
        );

        return to_route('backoffice.support-tickets.show', $ticket)->with('success', 'Estado atualizado.');
    }
}
