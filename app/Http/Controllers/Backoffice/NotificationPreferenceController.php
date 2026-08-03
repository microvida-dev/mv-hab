<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class NotificationPreferenceController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', NotificationPreference::class);

        return view('backoffice.communications.preferences.index', [
            'preferences' => $this->municipalScope
                ->notificationPreferences(
                    NotificationPreference::query(),
                    $this->currentUser(),
                )
                ->with('user')
                ->latest()
                ->paginate(25),
        ]);
    }
}
