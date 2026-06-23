<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\CommunicationLog;
use App\Models\NotificationPreference;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class NotificationPreferenceController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', CommunicationLog::class);

        return view('backoffice.communications.preferences.index', [
            'preferences' => NotificationPreference::query()->with('user')->latest()->paginate(25),
        ]);
    }
}
