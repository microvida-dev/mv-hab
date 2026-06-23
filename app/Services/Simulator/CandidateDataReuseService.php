<?php

namespace App\Services\Simulator;

use App\Enums\CandidateDataReuseProfileStatus;
use App\Models\AdhesionRegistration;
use App\Models\CandidateDataReuseProfile;
use App\Models\SimulationSession;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class CandidateDataReuseService
{
    public function createFromSimulation(User $user, SimulationSession $session): CandidateDataReuseProfile
    {
        $registration = AdhesionRegistration::query()
            ->where('user_id', $user->id)
            ->first();
        $household = $registration?->household()->with(['members', 'incomeRecords'])->first();
        $housing = $registration?->currentHousingSituation()->first();
        $documents = $registration?->documentSubmissions()->with('documentType')->get() ?? collect();

        return CandidateDataReuseProfile::query()->create([
            'user_id' => $user->id,
            'adhesion_registration_id' => $registration?->id,
            'profile_number' => $this->number(),
            'status' => CandidateDataReuseProfileStatus::Active,
            'registration_snapshot' => $registration instanceof AdhesionRegistration ? $this->registrationSnapshot($registration) : null,
            'household_snapshot' => $household?->toArray(),
            'income_snapshot' => $household?->incomeRecords()->get()->toArray(),
            'housing_snapshot' => $housing?->toArray(),
            'documents_snapshot' => $documents->map(fn ($document): array => [
                'document_type_id' => $document->document_type_id,
                'status' => $this->enumValue($document->getAttribute('status')),
                'submitted_at' => $this->dateTimeString($document->getAttribute('submitted_at')),
            ])->values()->all(),
            'source_payload' => [
                'simulation_session_id' => $session->id,
                'simulation_uuid' => $session->uuid,
                'result_status' => $this->enumValue($session->getAttribute('result_status')),
            ],
            'last_confirmed_at' => now(),
            'expires_at' => now()->addMonths(6),
            'created_from_simulation_session_id' => $session->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function registrationSnapshot(AdhesionRegistration $registration): array
    {
        return [
            'full_name' => $registration->full_name,
            'email' => $registration->email,
            'phone' => $registration->phone,
            'mobile_phone' => $registration->mobile_phone,
            'document_type' => $registration->document_type,
            'document_valid_until' => $this->dateString($registration->getAttribute('document_valid_until')),
            'nif' => $registration->nif,
            'birth_date' => $this->dateString($registration->getAttribute('birth_date')),
            'nationality' => $registration->nationality,
            'address' => $registration->address,
            'postal_code' => $registration->postal_code,
            'city' => $registration->city,
            'parish' => $registration->parish,
            'municipality' => $registration->municipality,
            'status' => $this->enumValue($registration->getAttribute('status')),
            'submitted_at' => $this->dateTimeString($registration->getAttribute('submitted_at')),
        ];
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return is_string($value->value) ? $value->value : (string) $value->value;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    private function dateString(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function dateTimeString(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function number(): string
    {
        return 'RDP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
