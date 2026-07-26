<?php

namespace App\Services\InternalAlerts;

use App\Enums\DocumentStatus;
use App\Enums\InternalAlertSeverity;
use App\Enums\InternalAlertType;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;

class InternalAlertDetector
{
    public function __construct(
        private readonly InternalAlertService $alerts,
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly AuditLogger $audit,
    ) {}

    public function detect(User $actor): int
    {
        abort_unless(
            $actor->hasPermission('internal_alerts.detect')
                && $this->municipalScope
                    ->hasMunicipalOrGlobalScope($actor),
            403,
        );

        $count = 0;

        $this->municipalScope
            ->documentSubmissions(DocumentSubmission::query(), $actor)
            ->whereIn('status', [DocumentStatus::Submitted->value, DocumentStatus::UnderReview->value])
            ->whereNotNull('application_id')
            ->limit(20)
            ->get()
            ->each(function (DocumentSubmission $document) use (&$count, $actor): void {
                $this->alerts->create(
                    InternalAlertType::DocumentsPending,
                    InternalAlertSeverity::Warning,
                    'Documento pendente de validação',
                    'Existe documentação submetida ou em análise que requer decisão dos serviços.',
                    $document,
                    ['application_id' => $document->application_id, 'assigned_role' => 'municipal_technician'],
                    $actor,
                );
                $count++;
            });

        $this->municipalScope
            ->applications(Application::query(), $actor)
            ->whereDoesntHave('administrativeProcess')
            ->whereIn('status', ['submitted', 'under_review'])
            ->limit(20)
            ->get()
            ->each(function (Application $application) use (&$count, $actor): void {
                $this->alerts->create(
                    InternalAlertType::ApplicationUnassigned,
                    InternalAlertSeverity::High,
                    'Candidatura sem técnico atribuído',
                    'A candidatura não tem processo administrativo atribuído.',
                    $application,
                    ['application_id' => $application->id, 'contest_id' => $application->contest_id, 'assigned_role' => 'municipal_technician'],
                    $actor,
                );
                $count++;
            });

        $this->municipalScope
            ->supportTickets(SupportTicket::query(), $actor)
            ->whereIn('status', ['open', 'pending_staff', 'in_progress'])
            ->limit(20)
            ->get()
            ->each(function (SupportTicket $ticket) use (&$count, $actor): void {
                $this->alerts->create(
                    InternalAlertType::TicketPending,
                    InternalAlertSeverity::Info,
                    'Pedido de apoio pendente',
                    'Existe um pedido de apoio que requer acompanhamento municipal.',
                    $ticket,
                    ['application_id' => $ticket->application_id, 'contest_id' => $ticket->contest_id, 'assigned_to' => $ticket->assigned_to],
                    $actor,
                );
                $count++;
            });

        $this->audit->record(
            AuditEvents::CREATE,
            module: 'notifications',
            action: 'internal_alert_detection',
            description: 'Deteção manual de alertas internos executada.',
            metadata: [
                'actor_id' => $actor->id,
                'alerts_processed' => $count,
            ],
        );

        return $count;
    }
}
