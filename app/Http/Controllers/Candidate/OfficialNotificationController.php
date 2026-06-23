<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\OfficialNotification;
use App\Services\Notifications\NotificationCenterService;
use App\Services\Notifications\OfficialNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OfficialNotificationController extends Controller
{
    public function __construct(
        private readonly OfficialNotificationService $service,
        private readonly NotificationCenterService $center,
    ) {}

    public function index(Request $request): View
    {
        $notifications = $this->center->forUser($this->authenticatedUser($request))->paginate(15);

        return view('candidate.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $this->center->unreadCount($this->authenticatedUser($request)),
        ]);
    }

    public function show(OfficialNotification $officialNotification): View
    {
        Gate::authorize('view', $officialNotification);

        return view('candidate.notifications.show', compact('officialNotification'));
    }

    public function markRead(Request $request, OfficialNotification $officialNotification): RedirectResponse
    {
        Gate::authorize('update', $officialNotification);
        $this->service->markRead($officialNotification, $this->authenticatedUser($request));

        return back()->with('success', 'Notificação marcada como lida.');
    }

    public function acknowledge(Request $request, OfficialNotification $officialNotification): RedirectResponse
    {
        Gate::authorize('acknowledge', $officialNotification);
        $this->service->acknowledge($officialNotification, $this->authenticatedUser($request));

        return back()->with('success', 'Tomada de conhecimento registada.');
    }

    public function archive(Request $request, OfficialNotification $officialNotification): RedirectResponse
    {
        Gate::authorize('archive', $officialNotification);
        $this->service->archive($officialNotification, $this->authenticatedUser($request));

        return to_route('candidate.notifications.index')->with('success', 'Notificação arquivada.');
    }
}
