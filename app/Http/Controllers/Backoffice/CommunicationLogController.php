<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\CommunicationChannel;
use App\Enums\OfficialNotificationChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommunicationLogRequest;
use App\Models\CommunicationLog;
use App\Models\User;
use App\Services\Notifications\CommunicationDeliveryService;
use App\Services\Notifications\CommunicationLogService;
use App\Services\Notifications\OfficialNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommunicationLogController extends Controller
{
    public function __construct(
        private readonly CommunicationLogService $service,
        private readonly CommunicationDeliveryService $deliveries,
        private readonly OfficialNotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CommunicationLog::class);
        $query = CommunicationLog::query()->with('recipient')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('event_code')) {
            $query->where('event_code', $request->string('event_code'));
        }

        return view('backoffice.communications.logs.index', [
            'communications' => $query->paginate(25)->withQueryString(),
            'users' => User::query()->orderBy('name')->limit(100)->get(),
            'channels' => CommunicationChannel::options(),
        ]);
    }

    public function show(CommunicationLog $communicationLog): View
    {
        Gate::authorize('view', $communicationLog);
        $communicationLog->load(['recipient', 'deliveries.attempts', 'receipts', 'templateVersion']);

        return view('backoffice.communications.logs.show', compact('communicationLog'));
    }

    public function store(StoreCommunicationLogRequest $request): RedirectResponse
    {
        Gate::authorize('create', CommunicationLog::class);
        $communication = $this->service->storeManual($request->validated(), $this->authenticatedUser($request));
        $channel = CommunicationChannel::from($request->validated('channel'));

        if (in_array($channel, [CommunicationChannel::InApp, CommunicationChannel::Internal], true)) {
            $recipient = $communication->recipient()->first();
            abort_unless($recipient instanceof User, 422);

            $this->notifications->createFromCommunication(
                $communication,
                $recipient,
                channel: $channel === CommunicationChannel::Internal ? OfficialNotificationChannel::Internal : OfficialNotificationChannel::InApp,
                actor: $this->authenticatedUser($request),
            );
        } else {
            $destination = match ($channel) {
                CommunicationChannel::Email => $communication->recipient_email,
                CommunicationChannel::Sms => $communication->recipient_phone,
                CommunicationChannel::Postal => $communication->recipient_address,
                default => null,
            };
            $delivery = $this->deliveries->create($communication, $channel, $destination);
            $this->deliveries->execute($delivery, $this->authenticatedUser($request));
        }

        return to_route('backoffice.communications.logs.show', $communication)->with('success', 'Comunicação criada.');
    }

    public function cancel(CommunicationLog $communicationLog): RedirectResponse
    {
        Gate::authorize('update', $communicationLog);
        $this->service->cancel($communicationLog, $this->currentUser());

        return back()->with('success', 'Comunicação cancelada.');
    }

    public function archive(CommunicationLog $communicationLog): RedirectResponse
    {
        Gate::authorize('update', $communicationLog);
        $this->service->archive($communicationLog);

        return back()->with('success', 'Comunicação arquivada.');
    }
}
