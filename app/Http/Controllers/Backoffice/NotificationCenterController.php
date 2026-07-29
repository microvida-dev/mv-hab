<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\GeneratedOfficialDocument;
use App\Models\NotificationTemplate;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class NotificationCenterController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', CommunicationLog::class);
        $user = $this->currentUser();
        $communications = $this->municipalScope
            ->communicationLogs(CommunicationLog::query(), $user);
        $deliveries = $this->municipalScope
            ->communicationDeliveries(CommunicationDelivery::query(), $user);
        $templates = $this->municipalScope
            ->notificationTemplates(NotificationTemplate::query(), $user);
        $documents = $this->municipalScope
            ->generatedOfficialDocuments(
                GeneratedOfficialDocument::query(),
                $user,
            );

        return view('backoffice.communications.dashboard', [
            'totals' => [
                'communications' => (clone $communications)->count(),
                'queued' => (clone $communications)->where('status', 'queued')->count(),
                'failed' => (clone $communications)
                    ->whereIn('status', ['failed', 'partially_sent'])
                    ->count(),
                'pending_configuration' => (clone $deliveries)->where('status', 'pending_configuration')->count(),
                'templates' => (clone $templates)->count(),
                'documents' => (clone $documents)->count(),
            ],
            'recent' => $communications
                ->with('recipient')
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
