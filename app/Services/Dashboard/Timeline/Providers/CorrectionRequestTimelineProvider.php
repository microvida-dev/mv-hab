<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineEventFactory;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;

class CorrectionRequestTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory(),
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('correction_requests.view')) {
            return [];
        }

        return collect()
            ->merge($this->openCorrectionRequests())
            ->merge($this->submittedResponses())
            ->values()
            ->all();
    }

    private function openCorrectionRequests(): array
    {
        return CorrectionRequest::query()
            ->whereNotNull('response_deadline_at')
            ->whereNotIn('status', ['completed', 'cancelled', 'expired'])
            ->orderBy('response_deadline_at')
            ->limit(20)
            ->get()
            ->map(fn (CorrectionRequest $request): TimelineEvent => $this->factory->make(
                id: 'correction-request-'.$request->getKey(),
                type: TimelineType::CorrectionRequest,
                title: 'Pedido de aperfeiçoamento pendente',
                description: trim(($request->request_number ?? 'Pedido').' · prazo de resposta'),
                route: route('backoffice.applications.index'),
                datetime: $request->response_deadline_at,
                priority: $request->response_deadline_at?->isPast()
                    ? TimelinePriority::Critical
                    : TimelinePriority::High,
                icon: 'document-warning',
                tone: $request->response_deadline_at?->isPast() ? 'danger' : 'warning',
                workspace: TimelineWorkspace::Applications,
                metadata: [
                    'correction_request_id' => $request->getKey(),
                    'request_number' => $request->request_number,
                    'status' => $request->status,
                ],
            ))
            ->all();
    }

    private function submittedResponses(): array
    {
        return CorrectionResponse::query()
            ->whereNotNull('submitted_at')
            ->whereIn('status', ['submitted', 'under_review'])
            ->orderBy('submitted_at')
            ->limit(20)
            ->get()
            ->map(fn (CorrectionResponse $response): TimelineEvent => $this->factory->make(
                id: 'correction-response-'.$response->getKey(),
                type: TimelineType::CorrectionResponse,
                title: 'Resposta a aperfeiçoamento recebida',
                description: trim(($response->response_number ?? 'Resposta').' · aguarda validação'),
                route: route('backoffice.correction-responses.show', [
                    'correctionResponse' => $response,
                ]),
                datetime: $response->submitted_at,
                priority: TimelinePriority::Medium,
                icon: 'document-check',
                tone: 'info',
                workspace: TimelineWorkspace::Applications,
                metadata: [
                    'correction_response_id' => $response->getKey(),
                    'response_number' => $response->response_number,
                    'status' => $response->status,
                ],
            ))
            ->all();
    }
}
