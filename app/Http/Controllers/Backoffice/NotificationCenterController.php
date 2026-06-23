<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\GeneratedOfficialDocument;
use App\Models\NotificationTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class NotificationCenterController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', CommunicationLog::class);

        return view('backoffice.communications.dashboard', [
            'totals' => [
                'communications' => CommunicationLog::query()->count(),
                'queued' => CommunicationLog::query()->where('status', 'queued')->count(),
                'failed' => CommunicationLog::query()->where('status', 'failed')->count(),
                'pending_configuration' => CommunicationDelivery::query()->where('status', 'pending_configuration')->count(),
                'templates' => NotificationTemplate::query()->count(),
                'documents' => GeneratedOfficialDocument::query()->count(),
            ],
            'recent' => CommunicationLog::query()->with('recipient')->latest()->limit(10)->get(),
        ]);
    }
}
