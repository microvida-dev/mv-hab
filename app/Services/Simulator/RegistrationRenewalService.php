<?php

namespace App\Services\Simulator;

use App\Enums\RegistrationRenewalStatus;
use App\Models\AdhesionRegistration;
use App\Models\RegistrationRenewal;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationRenewalService
{
    public function __construct(private readonly CandidateDataReuseService $dataReuseService) {}

    public function start(User $user, ?string $reason = null): RegistrationRenewal
    {
        $registration = $user->adhesionRegistration()->first();

        if (! $registration instanceof AdhesionRegistration) {
            throw ValidationException::withMessages([
                'registration' => 'É necessário existir Registo de Adesão antes de iniciar renovação.',
            ]);
        }

        return RegistrationRenewal::query()->create([
            'user_id' => $user->id,
            'adhesion_registration_id' => $registration->id,
            'renewal_number' => $this->number(),
            'status' => RegistrationRenewalStatus::InProgress,
            'reason' => $reason ?? 'candidate_update',
            'previous_snapshot' => $this->dataReuseService->registrationSnapshot($registration),
            'updated_snapshot' => $this->dataReuseService->registrationSnapshot($registration),
            'changed_fields' => [],
            'missing_fields' => $this->missingFields($registration),
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, RegistrationRenewal $renewal, array $data): RegistrationRenewal
    {
        $this->assertOwner($user, $renewal);

        $status = RegistrationRenewalStatus::tryFrom((string) $renewal->getRawOriginal('status'));

        if (! in_array($status, [RegistrationRenewalStatus::Draft, RegistrationRenewalStatus::InProgress], true)) {
            throw ValidationException::withMessages([
                'renewal' => 'A renovação já não pode ser alterada.',
            ]);
        }

        $snapshot = array_replace($this->arraySnapshot($renewal->getAttribute('updated_snapshot')), Arr::only($data, $this->allowedFields()));
        $changed = array_keys(array_diff_assoc($snapshot, $this->arraySnapshot($renewal->getAttribute('previous_snapshot'))));

        $renewal->forceFill([
            'status' => RegistrationRenewalStatus::InProgress,
            'updated_snapshot' => $snapshot,
            'changed_fields' => $changed,
            'missing_fields' => $this->missingFieldsFromArray($snapshot),
        ])->save();

        return $renewal->refresh();
    }

    public function submit(User $user, RegistrationRenewal $renewal): RegistrationRenewal
    {
        $this->assertOwner($user, $renewal);

        if ($this->arraySnapshot($renewal->getAttribute('missing_fields')) !== []) {
            throw ValidationException::withMessages([
                'renewal' => 'Complete os campos obrigatórios antes de submeter a renovação.',
            ]);
        }

        $registration = AdhesionRegistration::query()->find($renewal->adhesion_registration_id);

        if (! $registration instanceof AdhesionRegistration) {
            throw ValidationException::withMessages([
                'registration' => 'O Registo de Adesão associado não foi encontrado.',
            ]);
        }

        $registration->fill(Arr::only($this->arraySnapshot($renewal->getAttribute('updated_snapshot')), $this->allowedFields()));
        $registration->save();

        $renewal->forceFill([
            'status' => RegistrationRenewalStatus::Completed,
            'submitted_at' => now(),
            'completed_at' => now(),
        ])->save();

        return $renewal->refresh();
    }

    /**
     * @return list<string>
     */
    private function allowedFields(): array
    {
        return [
            'phone',
            'mobile_phone',
            'document_type',
            'document_valid_until',
            'address',
            'postal_code',
            'city',
            'parish',
            'municipality',
            'nationality',
        ];
    }

    /**
     * @return list<string>
     */
    private function missingFields(AdhesionRegistration $registration): array
    {
        return $this->missingFieldsFromArray($this->dataReuseService->registrationSnapshot($registration));
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return list<string>
     */
    private function missingFieldsFromArray(array $snapshot): array
    {
        $missing = [];

        foreach (AdhesionRegistration::REQUIRED_FIELDS as $field) {
            if (array_key_exists($field, $snapshot) && filled($snapshot[$field])) {
                continue;
            }

            if (in_array($field, ['accepts_terms', 'accepts_data_processing'], true)) {
                continue;
            }

            $missing[] = $field;
        }

        return $missing;
    }

    private function assertOwner(User $user, RegistrationRenewal $renewal): void
    {
        if ($renewal->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'renewal' => 'A renovação não pertence ao utilizador autenticado.',
            ]);
        }
    }

    private function number(): string
    {
        return 'REN-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }

    /**
     * @return array<string, mixed>
     */
    private function arraySnapshot(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
