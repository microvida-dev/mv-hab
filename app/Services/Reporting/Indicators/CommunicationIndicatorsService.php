<?php

namespace App\Services\Reporting\Indicators;

use App\Enums\CommunicationStatus;
use App\Models\CommunicationLog;
use App\Models\OfficialNotification;
use App\Services\Reporting\ReportFilterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CommunicationIndicatorsService
{
    public function __construct(private readonly ReportFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<CommunicationLog>
     */
    private function query(array $filters): Builder
    {
        return $this->filters
            ->applyDates(
                CommunicationLog::query(),
                $filters,
                'created_at',
            )
            ->when(
                $filters['municipality_id'] ?? null,
                fn (Builder $query, int $municipalityId): Builder => $query
                    ->whereHas(
                        'creator',
                        fn (Builder $creator): Builder => $creator->where(
                            'municipality_id',
                            $municipalityId,
                        ),
                    )
                    ->where(function (Builder $logs) use (
                        $municipalityId,
                    ): void {
                        $logs
                            ->whereNull('recipient_user_id')
                            ->orWhereHas(
                                'recipient',
                                fn (Builder $recipient): Builder => $recipient
                                    ->where(
                                        'municipality_id',
                                        $municipalityId,
                                    ),
                            );
                    }),
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where(
                    'status',
                    $status,
                ),
            );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countSentCommunications(array $filters): int
    {
        return $this->query($filters)->whereIn('status', [CommunicationStatus::Sent->value, CommunicationStatus::PartiallySent->value])->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countFailedCommunications(array $filters): int
    {
        return $this->query($filters)->where('status', CommunicationStatus::Failed->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countUnreadNotifications(array $filters): int
    {
        return $this->notificationQuery($filters)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int|string, mixed>
     */
    public function communicationsByEvent(array $filters): array
    {
        return $this->query($filters)->select('event_code', DB::raw('COUNT(*) as total'))->groupBy('event_code')->pluck('total', 'event_code')->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countPendingAcknowledgements(array $filters): int
    {
        return $this->notificationQuery($filters)
            ->where('requires_acknowledgement', true)
            ->whereNull('acknowledged_at')
            ->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<OfficialNotification>
     */
    private function notificationQuery(array $filters): Builder
    {
        return $this->filters
            ->applyDates(
                OfficialNotification::query(),
                $filters,
                'created_at',
            )
            ->when(
                $filters['municipality_id'] ?? null,
                fn (Builder $query, int $municipalityId): Builder => $query
                    ->whereHas(
                        'user',
                        fn (Builder $recipient): Builder => $recipient->where(
                            'municipality_id',
                            $municipalityId,
                        ),
                    )
                    ->where(function (Builder $notifications) use (
                        $municipalityId,
                    ): void {
                        $notifications
                            ->whereNull('application_id')
                            ->orWhereHas(
                                'application.program',
                                fn (Builder $program): Builder => $program
                                    ->where(
                                        'municipality_id',
                                        $municipalityId,
                                    ),
                            );
                    }),
            );
    }
}
