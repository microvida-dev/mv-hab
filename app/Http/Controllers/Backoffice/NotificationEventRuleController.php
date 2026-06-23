<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\CommunicationChannel;
use App\Enums\NotificationPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationEventRuleRequest;
use App\Http\Requests\UpdateNotificationEventRuleRequest;
use App\Models\NotificationEventRule;
use App\Models\NotificationTemplate;
use App\Services\Notifications\NotificationEventRuleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class NotificationEventRuleController extends Controller
{
    public function __construct(private readonly NotificationEventRuleService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', NotificationEventRule::class);

        return view('backoffice.communications.event-rules.index', [
            'rules' => NotificationEventRule::query()->with('template')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', NotificationEventRule::class);

        return view('backoffice.communications.event-rules.create', $this->formData());
    }

    public function store(StoreNotificationEventRuleRequest $request): RedirectResponse
    {
        Gate::authorize('create', NotificationEventRule::class);
        $this->service->store($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.communications.event-rules.index')->with('success', 'Regra criada.');
    }

    public function edit(NotificationEventRule $notificationEventRule): View
    {
        Gate::authorize('update', $notificationEventRule);

        return view('backoffice.communications.event-rules.edit', $this->formData() + compact('notificationEventRule'));
    }

    public function update(UpdateNotificationEventRuleRequest $request, NotificationEventRule $notificationEventRule): RedirectResponse
    {
        Gate::authorize('update', $notificationEventRule);
        $this->service->update($notificationEventRule, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.communications.event-rules.index')->with('success', 'Regra atualizada.');
    }

    public function activate(NotificationEventRule $notificationEventRule): RedirectResponse
    {
        Gate::authorize('update', $notificationEventRule);
        $this->service->setActive($notificationEventRule, true, $this->currentUser());

        return back()->with('success', 'Regra ativada.');
    }

    public function deactivate(NotificationEventRule $notificationEventRule): RedirectResponse
    {
        Gate::authorize('update', $notificationEventRule);
        $this->service->setActive($notificationEventRule, false, $this->currentUser());

        return back()->with('success', 'Regra desativada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'templates' => NotificationTemplate::query()->where('status', 'active')->orderBy('name')->get(),
            'channels' => CommunicationChannel::options(),
            'priorities' => NotificationPriority::options(),
        ];
    }
}
