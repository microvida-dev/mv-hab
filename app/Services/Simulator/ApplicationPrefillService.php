<?php

namespace App\Services\Simulator;

use App\Enums\ApplicationPrefillStatus;
use App\Enums\SimulationSessionStatus;
use App\Models\Application;
use App\Models\ApplicationPrefill;
use App\Models\Contest;
use App\Models\SimulationSession;
use App\Models\User;
use App\Services\Applications\ApplicationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApplicationPrefillService
{
    public function __construct(
        private readonly CandidateDataReuseService $dataReuseService,
        private readonly ApplicationService $applicationService,
        private readonly SimulationAuditService $auditService,
    ) {}

    public function createFromSimulation(User $user, SimulationSession $session, ?Application $application = null): ApplicationPrefill
    {
        if (! $session->belongsToUser($user)) {
            throw ValidationException::withMessages([
                'simulation' => 'A simulação não pertence ao utilizador autenticado.',
            ]);
        }

        $profile = $this->dataReuseService->createFromSimulation($user, $session);
        $targetApplication = $application ?? $this->draftFromRecommendation($user, $session);
        $warnings = [];

        if (! $targetApplication instanceof Application) {
            $warnings[] = 'Não foi possível criar automaticamente um rascunho de candidatura. Confirme os dados mínimos e escolha um concurso aberto.';
        }

        $prefill = ApplicationPrefill::query()->create([
            'user_id' => $user->id,
            'application_id' => $targetApplication?->id,
            'simulation_session_id' => $session->id,
            'candidate_data_reuse_profile_id' => $profile->id,
            'status' => ApplicationPrefillStatus::PendingConfirmation,
            'prefill_payload' => [
                'simulation_uuid' => $session->uuid,
                'registration' => $profile->registration_snapshot,
                'household' => $profile->household_snapshot,
                'income' => $profile->income_snapshot,
                'housing' => $profile->housing_snapshot,
                'recommended_contests' => $session->recommendedContests()->pluck('contest_id')->all(),
            ],
            'fields_included' => ['registration', 'household', 'income', 'housing'],
            'fields_excluded' => ['documents', 'declarations', 'formal_eligibility_decision'],
            'warnings' => $warnings,
            'expires_at' => now()->addDays(30),
        ]);

        $session->forceFill([
            'status' => SimulationSessionStatus::ConvertedToApplicationDraft,
            'converted_at' => now(),
            'application_id' => $targetApplication?->id,
        ])->save();

        $this->auditService->record($user, $session, 'update', 'Simulação convertida em pré-preenchimento de candidatura.');

        return $prefill->fresh(['application', 'simulationSession', 'candidateDataReuseProfile']) ?? $prefill;
    }

    public function confirm(User $user, ApplicationPrefill $prefill): ApplicationPrefill
    {
        $this->assertOwner($user, $prefill);

        $prefill->forceFill([
            'status' => ApplicationPrefillStatus::Confirmed,
            'confirmed_by_user_at' => now(),
        ])->save();

        return $prefill->refresh();
    }

    public function apply(User $user, ApplicationPrefill $prefill): ApplicationPrefill
    {
        $this->assertOwner($user, $prefill);

        if (ApplicationPrefillStatus::tryFrom((string) $prefill->getRawOriginal('status')) !== ApplicationPrefillStatus::Confirmed) {
            throw ValidationException::withMessages([
                'prefill' => 'Confirme o pré-preenchimento antes de o aplicar.',
            ]);
        }

        $application = $prefill->application;
        if (! $application instanceof Application || ! $application->isEditable()) {
            throw ValidationException::withMessages([
                'application' => 'O pré-preenchimento só pode ser aplicado a um rascunho editável.',
            ]);
        }

        return DB::transaction(function () use ($prefill, $application, $user): ApplicationPrefill {
            $application->fill([
                'candidate_notes' => trim(($application->candidate_notes ?? '')."\nDados revistos a partir de simulação indicativa."),
            ]);
            $application->forceFill(['updated_by' => $user->id])->save();

            $prefill->forceFill([
                'status' => ApplicationPrefillStatus::Applied,
                'applied_at' => now(),
            ])->save();

            return $prefill->refresh();
        });
    }

    public function cancel(User $user, ApplicationPrefill $prefill): ApplicationPrefill
    {
        $this->assertOwner($user, $prefill);

        $prefill->forceFill(['status' => ApplicationPrefillStatus::Cancelled])->save();

        return $prefill->refresh();
    }

    private function draftFromRecommendation(User $user, SimulationSession $session): ?Application
    {
        $recommendation = $session->recommendedContests()->orderByDesc('match_score')->first();

        if ($recommendation === null) {
            return null;
        }

        $contest = Contest::query()->find($recommendation->contest_id);

        if (! $contest instanceof Contest) {
            return null;
        }

        try {
            return $this->applicationService->createDraft($user, $contest, [
                'candidate_notes' => 'Rascunho criado a partir de simulação indicativa.',
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    private function assertOwner(User $user, ApplicationPrefill $prefill): void
    {
        if ($prefill->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'prefill' => 'O pré-preenchimento não pertence ao utilizador autenticado.',
            ]);
        }
    }
}
