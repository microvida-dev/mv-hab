<?php

namespace App\Services\Simulator;

use App\Enums\SimulationScope;
use App\Enums\SimulationSessionStatus;
use App\Models\Application;
use App\Models\SimulationSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SimulationSessionService
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array{score: float, missing_fields: list<string>, complete: bool}  $completeness
     */
    public function create(
        SimulationScope $scope,
        array $input,
        array $completeness,
        Request $request,
        ?User $user = null,
        ?Application $application = null,
    ): SimulationSession {
        $session = SimulationSession::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user?->id,
            'adhesion_registration_id' => is_numeric($input['registration_id'] ?? null) ? (int) $input['registration_id'] : null,
            'application_id' => $application?->id,
            'scope' => $scope,
            'status' => SimulationSessionStatus::InProgress,
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'source' => (string) Arr::get($input, 'source', 'web'),
            'ip_hash' => $this->hashNullable($request->ip()),
            'user_agent_hash' => $this->hashNullable($request->userAgent()),
        ]);

        $session->inputSnapshot()->create([
            'household_members_count' => $input['household_members_count'] ?? null,
            'adults_count' => $input['adults_count'] ?? null,
            'dependents_count' => $input['dependents_count'] ?? null,
            'disabled_members_count' => $input['disabled_members_count'] ?? null,
            'monthly_income' => $input['monthly_income'] ?? null,
            'annual_income' => $input['annual_income'] ?? null,
            'current_monthly_rent' => $input['current_monthly_rent'] ?? null,
            'housing_status' => $input['housing_status'] ?? null,
            'preferred_parishes' => $input['preferred_parishes'] ?? [],
            'preferred_typologies' => $input['preferred_typologies'] ?? [],
            'input_data' => $input,
            'completeness_score' => $completeness['score'],
            'contains_personal_data' => $user instanceof User,
        ]);

        return $session->fresh(['inputSnapshot']) ?? $session;
    }

    public function markSaved(SimulationSession $session): SimulationSession
    {
        $session->forceFill([
            'status' => SimulationSessionStatus::Saved,
            'saved_at' => now(),
        ])->save();

        return $session->refresh();
    }

    private function hashNullable(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return hash('sha256', config('app.key').'|'.$value);
    }
}
