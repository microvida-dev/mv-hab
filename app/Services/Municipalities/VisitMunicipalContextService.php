<?php

declare(strict_types=1);

namespace App\Services\Municipalities;

use App\Models\Application;
use App\Models\Contest;
use App\Models\HousingUnit;
use App\Models\HousingVisit;
use App\Models\Program;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Services\Platform\PlatformOperatorScopeService;
use Illuminate\Validation\ValidationException;

final class VisitMunicipalContextService
{
    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function municipalityForAvailabilityData(
        array $data,
        User $actor,
        ?VisitAvailability $existing = null,
    ): int {
        $existingMunicipalityId = $existing instanceof VisitAvailability
            ? $this->validateAvailabilityForActor($existing, $actor)
            : null;

        $contest = $this->contest(
            $this->nullableId($data, 'contest_id'),
            'contest_id',
        );
        $housingUnit = $this->housingUnit(
            $this->nullableId($data, 'housing_unit_id'),
            'housing_unit_id',
        );

        if (! $contest instanceof Contest
            && ! $housingUnit instanceof HousingUnit) {
            $this->fail(
                'availability',
                'A disponibilidade deve estar associada a um concurso ou a uma habitação municipal.',
            );
        }

        $municipalityId = $this->singleMunicipality([
            $this->contestMunicipality($contest, 'contest_id'),
            $this->housingUnitMunicipality(
                $housingUnit,
                'housing_unit_id',
            ),
        ], 'availability');

        $staff = $this->user(
            $this->nullableId($data, 'staff_user_id'),
            'staff_user_id',
        );
        $this->assertStaffMunicipality($staff, $municipalityId);
        $this->assertActorCanAccessMunicipality($actor, $municipalityId);

        if ($existingMunicipalityId !== null
            && $existingMunicipalityId !== $municipalityId) {
            $this->fail(
                'availability',
                'Uma disponibilidade não pode ser transferida para outro Município.',
            );
        }

        return $municipalityId;
    }

    public function validateAvailability(
        VisitAvailability $availability,
    ): int {
        $availability->loadMissing([
            'contest.program',
            'housingUnit',
            'staff',
        ]);

        if ($availability->municipality_id === null) {
            $this->fail(
                'availability',
                'A disponibilidade não possui contexto municipal válido.',
            );
        }

        if ($availability->contest_id === null
            && $availability->housing_unit_id === null) {
            $this->fail(
                'availability',
                'A disponibilidade não possui uma origem municipal.',
            );
        }

        $contest = $this->requiredRelation(
            $availability->contest_id,
            $availability->contest,
            'contest_id',
        );
        $housingUnit = $this->requiredRelation(
            $availability->housing_unit_id,
            $availability->housingUnit,
            'housing_unit_id',
        );
        $staff = $this->requiredRelation(
            $availability->staff_user_id,
            $availability->staff,
            'staff_user_id',
        );

        $municipalityId = $this->singleMunicipality([
            $this->contestMunicipality($contest, 'contest_id'),
            $this->housingUnitMunicipality(
                $housingUnit,
                'housing_unit_id',
            ),
        ], 'availability');

        $this->assertCanonicalMunicipality(
            (int) $availability->municipality_id,
            $municipalityId,
            'availability',
        );
        $this->assertStaffMunicipality($staff, $municipalityId);

        return $municipalityId;
    }

    public function validateAvailabilityForActor(
        VisitAvailability $availability,
        User $actor,
    ): int {
        $municipalityId = $this->validateAvailability($availability);
        $this->assertActorCanAccessMunicipality($actor, $municipalityId);

        return $municipalityId;
    }

    public function validateSlot(VisitSlot $slot): int
    {
        $slot->loadMissing([
            'availability.contest.program',
            'availability.housingUnit',
            'availability.staff',
            'contest.program',
            'housingUnit',
            'staff',
        ]);

        if ($slot->municipality_id === null) {
            $this->fail(
                'visit_slot_id',
                'O horário não possui contexto municipal válido.',
            );
        }

        $availability = $this->requiredRelation(
            $slot->visit_availability_id,
            $slot->availability,
            'visit_slot_id',
        );

        if (! $availability instanceof VisitAvailability) {
            $this->fail(
                'visit_slot_id',
                'O horário não possui uma disponibilidade válida.',
            );
        }

        $municipalityId = $this->validateAvailability($availability);
        $this->assertCanonicalMunicipality(
            (int) $slot->municipality_id,
            $municipalityId,
            'visit_slot_id',
        );

        $contest = $this->requiredRelation(
            $slot->contest_id,
            $slot->contest,
            'contest_id',
        );
        $housingUnit = $this->requiredRelation(
            $slot->housing_unit_id,
            $slot->housingUnit,
            'housing_unit_id',
        );
        $staff = $this->requiredRelation(
            $slot->staff_user_id,
            $slot->staff,
            'staff_user_id',
        );

        $this->assertReplicatedRelation(
            $availability->contest_id,
            $slot->contest_id,
            'contest_id',
        );
        $this->assertReplicatedRelation(
            $availability->housing_unit_id,
            $slot->housing_unit_id,
            'housing_unit_id',
        );
        $this->assertReplicatedRelation(
            $availability->staff_user_id,
            $slot->staff_user_id,
            'staff_user_id',
        );
        $this->assertOptionalMunicipality(
            $this->contestMunicipality($contest, 'contest_id'),
            $municipalityId,
            'contest_id',
        );
        $this->assertOptionalMunicipality(
            $this->housingUnitMunicipality(
                $housingUnit,
                'housing_unit_id',
            ),
            $municipalityId,
            'housing_unit_id',
        );
        $this->assertStaffMunicipality($staff, $municipalityId);

        return $municipalityId;
    }

    public function validateSlotForActor(
        VisitSlot $slot,
        User $actor,
    ): int {
        $municipalityId = $this->validateSlot($slot);
        $this->assertActorCanAccessMunicipality($actor, $municipalityId);

        return $municipalityId;
    }

    /**
     * @return array{
     *     municipality_id: int,
     *     contest: Contest|null,
     *     housing_unit: HousingUnit|null
     * }
     */
    public function bookingContext(
        VisitSlot $slot,
        ?Application $application,
        ?int $contestId,
        ?int $housingUnitId,
    ): array {
        $municipalityId = $this->validateSlot($slot);
        $applicationMunicipalityId = $this->applicationMunicipality(
            $application,
            'application_id',
        );

        $requestedContest = $this->contest(
            $contestId,
            'contest_id',
        );
        $requestedHousingUnit = $this->housingUnit(
            $housingUnitId,
            'housing_unit_id',
        );

        $applicationContest = $application instanceof Application
            ? $application->contest
            : null;
        $contest = $slot->contest
            ?? $applicationContest
            ?? $requestedContest;
        $housingUnit = $slot->housingUnit
            ?? $requestedHousingUnit;

        $this->assertSameRelatedRecord(
            $slot->contest_id,
            $application?->contest_id,
            'contest_id',
        );
        $this->assertSameRelatedRecord(
            $slot->contest_id,
            $requestedContest?->id,
            'contest_id',
        );
        $this->assertSameRelatedRecord(
            $application?->contest_id,
            $requestedContest?->id,
            'contest_id',
        );
        $this->assertSameRelatedRecord(
            $slot->housing_unit_id,
            $requestedHousingUnit?->id,
            'housing_unit_id',
        );

        foreach ([
            [$applicationMunicipalityId, 'application_id'],
            [$this->contestMunicipality($contest, 'contest_id'), 'contest_id'],
            [
                $this->housingUnitMunicipality(
                    $housingUnit,
                    'housing_unit_id',
                ),
                'housing_unit_id',
            ],
        ] as [$relatedMunicipalityId, $field]) {
            $this->assertOptionalMunicipality(
                $relatedMunicipalityId,
                $municipalityId,
                $field,
            );
        }

        return [
            'municipality_id' => $municipalityId,
            'contest' => $contest,
            'housing_unit' => $housingUnit,
        ];
    }

    public function validateVisit(HousingVisit $visit): int
    {
        $visit->loadMissing([
            'slot.availability.contest.program',
            'slot.availability.housingUnit',
            'slot.availability.staff',
            'slot.contest.program',
            'slot.housingUnit',
            'slot.staff',
            'application.program',
            'application.contest.program',
            'contest.program',
            'housingUnit',
            'staff',
        ]);

        if ($visit->municipality_id === null) {
            $this->fail(
                'visit',
                'A visita não possui contexto municipal válido.',
            );
        }

        $slot = $this->requiredRelation(
            $visit->visit_slot_id,
            $visit->slot,
            'visit_slot_id',
        );

        if (! $slot instanceof VisitSlot) {
            $this->fail(
                'visit_slot_id',
                'A visita deve estar associada a um horário válido.',
            );
        }

        $municipalityId = $this->validateSlot($slot);
        $this->assertCanonicalMunicipality(
            (int) $visit->municipality_id,
            $municipalityId,
            'visit',
        );

        $application = $this->requiredRelation(
            $visit->application_id,
            $visit->application,
            'application_id',
        );
        $contest = $this->requiredRelation(
            $visit->contest_id,
            $visit->contest,
            'contest_id',
        );
        $housingUnit = $this->requiredRelation(
            $visit->housing_unit_id,
            $visit->housingUnit,
            'housing_unit_id',
        );
        $staff = $this->requiredRelation(
            $visit->staff_user_id,
            $visit->staff,
            'staff_user_id',
        );

        if ($application instanceof Application
            && (int) $application->user_id
                !== (int) $visit->candidate_user_id) {
            $this->fail(
                'application_id',
                'A candidatura não pertence ao candidato associado à visita.',
            );
        }

        $this->assertReplicatedRelation(
            $slot->contest_id,
            $application instanceof Application
                ? $application->contest_id
                : null,
            'contest_id',
            $application instanceof Application,
        );
        $this->assertReplicatedRelation(
            $slot->contest_id,
            $contest instanceof Contest ? $contest->id : null,
            'contest_id',
        );
        $this->assertReplicatedRelation(
            $application instanceof Application
                ? $application->contest_id
                : null,
            $contest instanceof Contest ? $contest->id : null,
            'contest_id',
            $application instanceof Application,
        );
        $this->assertReplicatedRelation(
            $slot->housing_unit_id,
            $housingUnit instanceof HousingUnit
                ? $housingUnit->id
                : null,
            'housing_unit_id',
        );

        foreach ([
            [
                $this->applicationMunicipality(
                    $application instanceof Application
                        ? $application
                        : null,
                    'application_id',
                ),
                'application_id',
            ],
            [
                $this->contestMunicipality(
                    $contest instanceof Contest ? $contest : null,
                    'contest_id',
                ),
                'contest_id',
            ],
            [
                $this->housingUnitMunicipality(
                    $housingUnit instanceof HousingUnit
                        ? $housingUnit
                        : null,
                    'housing_unit_id',
                ),
                'housing_unit_id',
            ],
        ] as [$relatedMunicipalityId, $field]) {
            $this->assertOptionalMunicipality(
                $relatedMunicipalityId,
                $municipalityId,
                $field,
            );
        }

        $this->assertStaffMunicipality(
            $staff instanceof User ? $staff : null,
            $municipalityId,
        );

        return $municipalityId;
    }

    public function validateVisitForActor(
        HousingVisit $visit,
        User $actor,
    ): int {
        $municipalityId = $this->validateVisit($visit);
        $this->assertActorCanAccessMunicipality($actor, $municipalityId);

        return $municipalityId;
    }

    public function validateCandidateVisit(
        HousingVisit $visit,
        User $candidate,
    ): int {
        if (! $visit->belongsToCandidate($candidate)) {
            $this->fail(
                'visit',
                'A visita não pertence ao candidato autenticado.',
            );
        }

        return $this->validateVisit($visit);
    }

    public function validateRescheduling(
        HousingVisit $visit,
        VisitSlot $newSlot,
        User $actor,
    ): int {
        $visitMunicipalityId = $visit->belongsToCandidate($actor)
            ? $this->validateCandidateVisit($visit, $actor)
            : $this->validateVisitForActor($visit, $actor);
        $slotMunicipalityId = $this->validateSlot($newSlot);

        if ($visitMunicipalityId !== $slotMunicipalityId) {
            $this->fail(
                'new_visit_slot_id',
                'A visita não pode ser reagendada para outro Município.',
            );
        }

        $this->assertSameRelatedRecord(
            $visit->contest_id,
            $newSlot->contest_id,
            'new_visit_slot_id',
        );
        $this->assertSameRelatedRecord(
            $visit->housing_unit_id,
            $newSlot->housing_unit_id,
            'new_visit_slot_id',
        );

        return $visitMunicipalityId;
    }

    public function assertActorCanAccessMunicipality(
        User $actor,
        int $municipalityId,
    ): void {
        if ($this->platformScope->hasGlobalScope($actor)) {
            return;
        }

        if ($actor->municipality_id !== null
            && (int) $actor->municipality_id === $municipalityId) {
            return;
        }

        $this->fail(
            'municipality',
            'O recurso não pertence ao âmbito municipal do utilizador.',
        );
    }

    private function applicationMunicipality(
        ?Application $application,
        string $field,
    ): ?int {
        if (! $application instanceof Application) {
            return null;
        }

        $application->loadMissing([
            'program',
            'contest.program',
        ]);

        $program = $application->getRelation('program');
        $contest = $application->getRelation('contest');
        $contestProgram = $contest instanceof Contest
            ? $contest->getRelation('program')
            : null;
        $programMunicipalityId = $program instanceof Program
            ? $program->getAttribute('municipality_id')
            : null;
        $contestMunicipalityId = $contestProgram instanceof Program
            ? $contestProgram->getAttribute('municipality_id')
            : null;

        if (! $program instanceof Program
            || ! $contest instanceof Contest
            || ! $contestProgram instanceof Program
            || (int) $application->program_id
                !== (int) $contest->program_id
            || ! is_numeric($programMunicipalityId)
            || ! is_numeric($contestMunicipalityId)
            || (int) $programMunicipalityId
                !== (int) $contestMunicipalityId) {
            $this->fail(
                $field,
                'A candidatura não possui um contexto municipal coerente.',
            );
        }

        return (int) $programMunicipalityId;
    }

    private function contestMunicipality(
        mixed $contest,
        string $field,
    ): ?int {
        if ($contest === null) {
            return null;
        }

        if (! $contest instanceof Contest) {
            $this->fail($field, 'O concurso indicado não existe.');
        }

        $contest->loadMissing('program');
        $program = $contest->getRelation('program');
        $municipalityId = $program instanceof Program
            ? $program->getAttribute('municipality_id')
            : null;

        if (! is_numeric($municipalityId)) {
            $this->fail(
                $field,
                'O concurso não possui um Município válido.',
            );
        }

        return (int) $municipalityId;
    }

    private function housingUnitMunicipality(
        mixed $housingUnit,
        string $field,
    ): ?int {
        if ($housingUnit === null) {
            return null;
        }

        if (! $housingUnit instanceof HousingUnit
            || ! is_numeric(
                $housingUnit->getAttribute('municipality_id'),
            )) {
            $this->fail(
                $field,
                'A habitação não possui um Município válido.',
            );
        }

        return (int) $housingUnit->getAttribute('municipality_id');
    }

    /**
     * @param  list<int|null>  $municipalityIds
     */
    private function singleMunicipality(
        array $municipalityIds,
        string $field,
    ): int {
        $municipalityIds = array_values(array_unique(array_filter(
            $municipalityIds,
            static fn (?int $id): bool => $id !== null,
        )));

        if (count($municipalityIds) !== 1) {
            $this->fail(
                $field,
                'As relações indicadas não pertencem ao mesmo Município.',
            );
        }

        return $municipalityIds[0];
    }

    private function assertCanonicalMunicipality(
        int $canonicalMunicipalityId,
        int $relatedMunicipalityId,
        string $field,
    ): void {
        if ($canonicalMunicipalityId !== $relatedMunicipalityId) {
            $this->fail(
                $field,
                'O contexto municipal guardado não coincide com as relações do recurso.',
            );
        }
    }

    private function assertOptionalMunicipality(
        ?int $relatedMunicipalityId,
        int $municipalityId,
        string $field,
    ): void {
        if ($relatedMunicipalityId !== null
            && $relatedMunicipalityId !== $municipalityId) {
            $this->fail(
                $field,
                'A relação indicada pertence a outro Município.',
            );
        }
    }

    private function assertStaffMunicipality(
        mixed $staff,
        int $municipalityId,
    ): void {
        if ($staff === null) {
            return;
        }

        if (! $staff instanceof User
            || $staff->municipality_id === null
            || (int) $staff->municipality_id !== $municipalityId) {
            $this->fail(
                'staff_user_id',
                'O técnico indicado não pertence ao Município do recurso.',
            );
        }
    }

    private function assertSameRelatedRecord(
        mixed $firstId,
        mixed $secondId,
        string $field,
    ): void {
        if ($firstId !== null
            && $secondId !== null
            && (int) $firstId !== (int) $secondId) {
            $this->fail(
                $field,
                'As relações indicadas não representam o mesmo contexto processual.',
            );
        }
    }

    private function assertReplicatedRelation(
        mixed $sourceId,
        mixed $replicatedId,
        string $field,
        bool $required = true,
    ): void {
        if (! $required) {
            return;
        }

        if (($sourceId === null) !== ($replicatedId === null)
            || ($sourceId !== null
                && (int) $sourceId !== (int) $replicatedId)) {
            $this->fail(
                $field,
                'A relação replicada não coincide com o contexto processual de origem.',
            );
        }
    }

    private function contest(
        ?int $id,
        string $field,
    ): ?Contest {
        if ($id === null) {
            return null;
        }

        $contest = Contest::query()
            ->with('program')
            ->find($id);

        if (! $contest instanceof Contest) {
            $this->fail($field, 'O concurso indicado não existe.');
        }

        return $contest;
    }

    private function housingUnit(
        ?int $id,
        string $field,
    ): ?HousingUnit {
        if ($id === null) {
            return null;
        }

        $housingUnit = HousingUnit::query()->find($id);

        if (! $housingUnit instanceof HousingUnit) {
            $this->fail($field, 'A habitação indicada não existe.');
        }

        return $housingUnit;
    }

    private function user(
        ?int $id,
        string $field,
    ): ?User {
        if ($id === null) {
            return null;
        }

        $user = User::query()->find($id);

        if (! $user instanceof User) {
            $this->fail($field, 'O utilizador indicado não existe.');
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function nullableId(
        array $data,
        string $field,
    ): ?int {
        $value = $data[$field] ?? null;

        return $value === null || $value === ''
            ? null
            : (int) $value;
    }

    private function requiredRelation(
        mixed $foreignId,
        mixed $relation,
        string $field,
    ): mixed {
        if ($foreignId !== null && $relation === null) {
            $this->fail(
                $field,
                'A relação indicada deixou de estar disponível.',
            );
        }

        return $relation;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
