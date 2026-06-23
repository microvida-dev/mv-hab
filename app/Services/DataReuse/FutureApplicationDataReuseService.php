<?php

namespace App\Services\DataReuse;

use App\Enums\DataReuseStatus;
use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Models\Application;
use App\Models\CandidateDataReuseProfile;
use App\Models\FutureApplicationDataReuse;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\ProcessTracking\ProcessTimelineService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FutureApplicationDataReuseService
{
    public function __construct(
        private readonly DataReuseEligibilityService $eligibility,
        private readonly DataReuseSnapshotService $snapshots,
        private readonly ProcessTimelineService $timeline,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  list<string>  $sections
     */
    public function create(User $user, CandidateDataReuseProfile $profile, ?Application $targetApplication, array $sections): FutureApplicationDataReuse
    {
        $status = $this->eligibility->statusFor($user, $profile, $targetApplication);
        if ($status === DataReuseStatus::Blocked || $status === DataReuseStatus::Expired) {
            throw ValidationException::withMessages(['reuse' => 'Os dados não podem ser reutilizados neste momento.']);
        }

        $reuse = new FutureApplicationDataReuse(['sections' => $sections]);
        $reuse->forceFill([
            'user_id' => $user->id,
            'source_application_id' => $profile->created_from_application_id,
            'source_reuse_profile_id' => $profile->id,
            'target_application_id' => $targetApplication?->id,
            'status' => DataReuseStatus::RequiresConfirmation,
            'source_snapshot' => $this->snapshots->fromProfile($profile),
            'warnings' => $this->eligibility->warningsFor($profile),
            'expires_at' => now()->addDays(30),
        ])->save();

        return $reuse->refresh();
    }

    public function confirm(FutureApplicationDataReuse $reuse, User $user, Application $targetApplication): FutureApplicationDataReuse
    {
        if ($reuse->user_id !== $user->id || $targetApplication->user_id !== $user->id || ! $targetApplication->isEditable()) {
            throw ValidationException::withMessages(['reuse' => 'Não é possível aplicar reutilização de dados nesta candidatura.']);
        }

        return DB::transaction(function () use ($reuse, $user, $targetApplication): FutureApplicationDataReuse {
            $reuse->forceFill([
                'target_application_id' => $targetApplication->id,
                'status' => DataReuseStatus::Applied,
                'confirmed_at' => now(),
                'applied_at' => now(),
            ])->save();

            $this->timeline->record(
                application: $targetApplication,
                type: TimelineEventType::DataReused,
                visibility: TimelineEventVisibility::CandidateVisible,
                title: 'Dados reutilizados em candidatura',
                description: 'O candidato confirmou a reutilização de dados próprios após revisão.',
                actor: $user,
                related: $reuse,
                metadata: ['sections' => $reuse->sections],
            );

            $this->auditLogger->record(AuditEvents::UPDATE, $reuse, 'applications', 'future_data_reuse_apply', 'Reutilização futura de dados confirmada.');

            return $reuse->refresh();
        });
    }
}
