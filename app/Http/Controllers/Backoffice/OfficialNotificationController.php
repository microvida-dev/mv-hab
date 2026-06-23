<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OfficialNotificationChannel;
use App\Enums\OfficialNotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfficialNotificationRequest;
use App\Models\OfficialNotification;
use App\Services\Notifications\OfficialNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OfficialNotificationController extends Controller
{
    public function __construct(private readonly OfficialNotificationService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', OfficialNotification::class);
        $notifications = OfficialNotification::query()->with(['user', 'application', 'notifiable'])->latest()->paginate(20);

        return view('backoffice.official-notifications.index', compact('notifications'));
    }

    public function create(): View
    {
        Gate::authorize('create', OfficialNotification::class);

        return view('backoffice.official-notifications.show', [
            'officialNotification' => null,
            'types' => OfficialNotificationType::options(),
            'channels' => OfficialNotificationChannel::options(),
        ]);
    }

    public function store(StoreOfficialNotificationRequest $request): RedirectResponse
    {
        Gate::authorize('create', OfficialNotification::class);
        $notification = $this->service->store($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.official-notifications.show', $notification)->with('success', 'Notificação interna criada.');
    }

    public function show(OfficialNotification $officialNotification): View
    {
        Gate::authorize('view', $officialNotification);
        $officialNotification->load(['user', 'application', 'notifiable', 'createdBy']);

        return view('backoffice.official-notifications.show', compact('officialNotification'));
    }

    public function markSent(Request $request, OfficialNotification $officialNotification): RedirectResponse
    {
        Gate::authorize('update', $officialNotification);
        $this->service->markSent($officialNotification);

        return back();
    }

    public function markFailed(Request $request, OfficialNotification $officialNotification): RedirectResponse
    {
        Gate::authorize('update', $officialNotification);
        $this->service->markFailed($officialNotification, $request->input('failure_reason'));

        return back()->with('success', 'Notificação marcada como falhada.');
    }
}
