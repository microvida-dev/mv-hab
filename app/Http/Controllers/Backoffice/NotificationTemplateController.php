<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\CommunicationChannel;
use App\Enums\TemplateType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationTemplateRequest;
use App\Http\Requests\UpdateNotificationTemplateRequest;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use App\Models\TemplateVariable;
use App\Services\Notifications\NotificationTemplateService;
use App\Services\Notifications\TemplateRenderingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class NotificationTemplateController extends Controller
{
    public function __construct(private readonly NotificationTemplateService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', NotificationTemplate::class);

        return view('backoffice.communications.templates.index', [
            'templates' => NotificationTemplate::query()->with('activeVersion')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', NotificationTemplate::class);

        return view('backoffice.communications.templates.create', [
            'channels' => CommunicationChannel::options(),
            'types' => TemplateType::options(),
        ]);
    }

    public function store(StoreNotificationTemplateRequest $request): RedirectResponse
    {
        Gate::authorize('create', NotificationTemplate::class);
        $template = $this->service->store($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.communications.templates.show', $template)->with('success', 'Template criado.');
    }

    public function show(NotificationTemplate $notificationTemplate): View
    {
        Gate::authorize('view', $notificationTemplate);
        $notificationTemplate->load(['versions', 'activeVersion', 'eventRules']);

        return view('backoffice.communications.templates.show', compact('notificationTemplate'));
    }

    public function edit(NotificationTemplate $notificationTemplate): View
    {
        Gate::authorize('update', $notificationTemplate);

        return view('backoffice.communications.templates.edit', [
            'notificationTemplate' => $notificationTemplate,
            'channels' => CommunicationChannel::options(),
            'types' => TemplateType::options(),
        ]);
    }

    public function update(UpdateNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate): RedirectResponse
    {
        Gate::authorize('update', $notificationTemplate);
        $this->service->update($notificationTemplate, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.communications.templates.show', $notificationTemplate)->with('success', 'Template atualizado com nova versão.');
    }

    public function archive(NotificationTemplate $notificationTemplate): RedirectResponse
    {
        Gate::authorize('update', $notificationTemplate);
        $this->service->archive($notificationTemplate, $this->currentUser());

        return back()->with('success', 'Template arquivado.');
    }

    public function preview(NotificationTemplate $notificationTemplate, TemplateRenderingService $renderer): View
    {
        Gate::authorize('view', $notificationTemplate);
        $version = $notificationTemplate->activeVersion;

        if (! $version instanceof NotificationTemplateVersion) {
            $fallbackVersion = $notificationTemplate->versions()->first();
            $version = $fallbackVersion instanceof NotificationTemplateVersion ? $fallbackVersion : null;
        }

        abort_unless($version instanceof NotificationTemplateVersion, 404);

        $variables = TemplateVariable::query()->where('is_active', true)->pluck('example_value', 'code')->all();
        $rendered = $renderer->render([
            'subject' => $version->subject ?? $notificationTemplate->subject,
            'title' => $version->title ?? $notificationTemplate->title,
            'body' => $version->body ?? $notificationTemplate->body,
            'html_body' => $version->html_body ?? $notificationTemplate->html_body,
        ], $variables);

        return view('backoffice.communications.templates.preview', compact('notificationTemplate', 'rendered'));
    }
}
