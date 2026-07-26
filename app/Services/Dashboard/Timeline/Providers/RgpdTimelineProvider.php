<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\DataSubjectRequestStatus;
use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;
use App\Services\Rgpd\PrivacyMunicipalScopeService;

class RgpdTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory,
        private readonly PrivacyMunicipalScopeService $scope = new PrivacyMunicipalScopeService,
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('privacy.view')) {
            return [];
        }

        return $this->scope
            ->requests(DataSubjectRequest::query(), $user)
            ->whereNotNull('due_at')
            ->whereNotIn('status', [
                DataSubjectRequestStatus::Completed->value,
                DataSubjectRequestStatus::Rejected->value,
                DataSubjectRequestStatus::Cancelled->value,
                DataSubjectRequestStatus::Closed->value,
            ])
            ->orderBy('due_at')
            ->limit(20)
            ->get()
            ->map(fn (DataSubjectRequest $request): TimelineEvent => $this->factory->make(
                id: 'rgpd-request-'.$request->getKey(),
                type: TimelineType::RgpdRequest,
                title: 'Pedido RGPD com prazo',
                description: trim(($request->request_number ?? 'Pedido RGPD').' · '.$request->request_type->label()),
                route: route('backoffice.security.privacy.requests.index'),
                datetime: $request->due_at,
                priority: $request->due_at?->isPast()
                    ? TimelinePriority::Critical
                    : TimelinePriority::High,
                icon: 'security',
                tone: $request->due_at?->isPast() ? 'danger' : 'warning',
                workspace: TimelineWorkspace::Administration,
                metadata: [
                    'data_subject_request_id' => $request->getKey(),
                    'request_number' => $request->request_number,
                    'status' => $request->status->value,
                    'type' => $request->request_type->value,
                ],
            ))
            ->all();
    }
}
