<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationPreferenceRequest;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationPreferenceController extends Controller
{
    public function __construct(private readonly NotificationPreferenceService $service) {}

    public function edit(Request $request): View
    {
        $preference = $this->service->for($this->authenticatedUser($request));
        Gate::authorize('view', $preference);

        return view('candidate.notification-preferences.edit', compact('preference'));
    }

    public function update(UpdateNotificationPreferenceRequest $request, AuditLogger $audit): RedirectResponse
    {
        $preference = $this->service->for($this->authenticatedUser($request));
        Gate::authorize('update', $preference);
        $this->service->update($this->authenticatedUser($request), $request->validated(), $audit);

        return back()->with('success', 'Preferências atualizadas.');
    }
}
