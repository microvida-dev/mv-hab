<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseStatus;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class CorrectionRequestTimelineProvider implements TimelineProviderInterface
{
    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('applications.view')) {
            return [];
        }

        return collect()
            ->merge($this->openRequests())
            ->merge($this->submittedResponses())
            ->values()
            ->all();
    }

    private function openRequests(): array
    {
        return CorrectionRequest::query()
            ->whereIn('status', [
                CorrectionRequestStatus::Issued->value,
                CorrectionRequestStatus::Open->value,
                CorrectionRequestStatus::PartiallyResponded->value,
            ])
            ->whereNotNull('response_deadline_at')
            ->whereDate('response_deadline_at', '<=', now()->addDays(2)->toDateString())
            ->orderBy('response_deadline_at')
            ->limit(8)
            ->get()
            ->map(fn (CorrectionRequest $request): TimelineEvent => new TimelineEvent(
                id: 'correction-request-'.$request->getKey(),
                type: 'correction-request',
                title: $request->response_deadline_at?->isPast()
                    ? 'Pedido de aperfeiçoamento expirado'
                    : 'Pedido de aperfeiçoamento com prazo próximo',
                description: trim(($request->request_number ?? 'Pedido').' · prazo '.$request->response_deadline_at?->format('d/m/Y H:i')),
                route: 'backoffice.correction-requests.show',
                datetime: $request->response_deadline_at,
                priority: $request->response_deadline_at?->isPast() ? 'critical' : 'high',
                icon: 'document',
                tone: $request->response_deadline_at?->isPast() ? 'danger' : 'warning',
                workspace: 'applications',
                metadata: [
                    'correction_request_id' => $request->getKey(),
                    'request_number' => $request->request_number,
                    'status' => $request->status?->value,
                ],
            ))
            ->all();
    }

    private function submittedResponses(): array
    {
        return CorrectionResponse::query()
            ->whereIn('status', [
                CorrectionResponseStatus::Submitted->value,
            ])
            ->orderBy('submitted_at')
            ->limit(8)
            ->get()
            ->map(fn (CorrectionResponse $response): TimelineEvent => new TimelineEvent(
                id: 'correction-response-'.$response->getKey(),
                type: 'correction-response',
                title: 'Resposta a aperfeiçoamento por analisar',
                description: trim('Resposta submetida · '.$response->submitted_at?->format('d/m/Y H:i')),
                route: 'backoffice.correction-responses.show',
                datetime: $response->submitted_at,
                priority: 'high',
                icon: 'document-check',
                tone: 'warning',
                workspace: 'applications',
                metadata: [
                    'correction_response_id' => $response->getKey(),
                    'correction_request_id' => $response->correction_request_id,
                    'status' => $response->status?->value,
                ],
            ))
            ->all();
    }
}
