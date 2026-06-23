<?php

namespace App\Services\Applications;

use App\Enums\ApplicationSnapshotType;
use App\Models\Application;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class ApplicationReceiptService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @return array<string, mixed>
     */
    public function data(Application $application): array
    {
        $application->loadMissing([
            'user',
            'adhesionRegistration',
            'contest',
            'program',
            'household.members',
            'applicationDocuments.documentType',
            'declarations',
            'snapshots',
        ]);

        $this->auditLogger->record(
            event: AuditEvents::ACCESS,
            auditable: $application,
            module: 'applications',
            action: 'receipt',
            description: 'Comprovativo de candidatura consultado.',
            metadata: ['application_number' => $application->application_number],
        );

        return [
            'application' => $application,
            'summary' => $application->snapshots
                ->firstWhere('snapshot_type', ApplicationSnapshotType::Summary)
                ->data ?? [],
        ];
    }
}
