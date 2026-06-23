<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArchiveCandidateNotificationRequest;
use App\Http\Requests\MarkCandidateNotificationReadRequest;
use App\Models\OfficialNotification;
use App\Services\CandidateNotifications\CandidateNotificationCenterService;
use App\Services\CandidateNotifications\CandidateNotificationReadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CandidateNotificationCenterController extends Controller
{
    public function __construct(
        private readonly CandidateNotificationCenterService $center,
        private readonly CandidateNotificationReadService $reader,
    ) {}

    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);

        return view('candidate.notifications.index', [
            'notifications' => $this->center->paginateFor($user),
            'unreadCount' => $this->center->counts($user)['unread'],
            'counts' => $this->center->counts($user),
            'center' => $this->center,
        ]);
    }

    public function markRead(MarkCandidateNotificationReadRequest $request, OfficialNotification $officialNotification): RedirectResponse
    {
        Gate::authorize('update', $officialNotification);
        $this->reader->markRead($officialNotification, $this->authenticatedUser($request));

        return back()->with('success', 'Notificação marcada como lida.');
    }

    public function archive(ArchiveCandidateNotificationRequest $request, OfficialNotification $officialNotification): RedirectResponse
    {
        Gate::authorize('archive', $officialNotification);
        $this->reader->archive($officialNotification, $this->authenticatedUser($request));

        return to_route('candidate.notifications.index')->with('success', 'Notificação arquivada.');
    }
}
