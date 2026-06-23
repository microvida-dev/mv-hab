<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationTemplateVersionRequest;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use App\Services\Notifications\NotificationTemplateVersionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class NotificationTemplateVersionController extends Controller
{
    public function __construct(private readonly NotificationTemplateVersionService $service) {}

    public function store(StoreNotificationTemplateVersionRequest $request, NotificationTemplate $notificationTemplate): RedirectResponse
    {
        Gate::authorize('update', $notificationTemplate);
        $version = $this->service->create($notificationTemplate, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.communications.template-versions.show', $version);
    }

    public function show(NotificationTemplateVersion $notificationTemplateVersion): View
    {
        Gate::authorize('view', $notificationTemplateVersion);

        return view('backoffice.communications.template-versions.show', compact('notificationTemplateVersion'));
    }

    public function approve(NotificationTemplateVersion $notificationTemplateVersion): RedirectResponse
    {
        Gate::authorize('approve', $notificationTemplateVersion);
        $this->service->approve($notificationTemplateVersion, $this->currentUser());

        return back()->with('success', 'Versão aprovada.');
    }

    public function activate(NotificationTemplateVersion $notificationTemplateVersion): RedirectResponse
    {
        Gate::authorize('approve', $notificationTemplateVersion);
        $this->service->activate($notificationTemplateVersion, $this->currentUser());

        return back()->with('success', 'Versão ativada.');
    }

    public function archive(NotificationTemplateVersion $notificationTemplateVersion): RedirectResponse
    {
        Gate::authorize('update', $notificationTemplateVersion);
        $this->service->archive($notificationTemplateVersion);

        return back()->with('success', 'Versão arquivada.');
    }
}
