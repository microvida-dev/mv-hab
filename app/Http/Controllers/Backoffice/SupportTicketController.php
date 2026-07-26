<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignSupportTicketRequest;
use App\Http\Requests\UpdateSupportTicketStatusRequest;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\CandidateExperience\CandidateSupportDashboardService;
use App\Services\Municipalities\MunicipalRecordScopeService;
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
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', SupportTicket::class);

        $user = $this->currentUser();

        return view('backoffice.support-tickets.index', [
            'tickets' => $this->municipalScope
                ->supportTickets(SupportTicket::query(), $user)
                ->visibleToBackofficeUser($user)
                ->with(['user', 'application.contest', 'assignee'])
                ->latest('last_message_at')
                ->paginate(20),
            'indicators' => $this->dashboard->indicators($user),
        ]);
    }

    public function show(SupportTicket $supportTicket): View
    {
        Gate::authorize('viewBackoffice', $supportTicket);
        $supportTicket->load(['user', 'application.contest', 'contest', 'housingUnit', 'assignee', 'messages.sender', 'attachments']);

        return view('backoffice.support-tickets.show', [
            'ticket' => $supportTicket,
            'statuses' => TicketStatus::options(),
            'staffUsers' => $this->municipalScope
                ->users(User::query(), $this->currentUser())
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function assign(AssignSupportTicketRequest $request, SupportTicket $supportTicket): RedirectResponse
    {
        Gate::authorize('assignBackoffice', $supportTicket);
        $staff = $this->municipalScope
            ->users(
                User::query(),
                $this->authenticatedUser($request),
            )
            ->findOrFail($request->integer('assigned_to'));
        $ticket = $this->assignments->assign($supportTicket, $staff, $this->authenticatedUser($request));

        return to_route('backoffice.support-tickets.show', $ticket)->with('success', 'Ticket atribuído.');
    }

    public function updateStatus(UpdateSupportTicketStatusRequest $request, SupportTicket $supportTicket): RedirectResponse
    {
        Gate::authorize('resolveBackoffice', $supportTicket);
        $ticket = $this->statuses->update(
            $supportTicket,
            TicketStatus::from((string) $request->validated('status')),
            $this->authenticatedUser($request),
            $request->validated('message'),
        );

        return to_route('backoffice.support-tickets.show', $ticket)->with('success', 'Estado atualizado.');
    }
}
