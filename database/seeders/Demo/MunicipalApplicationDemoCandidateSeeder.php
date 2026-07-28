<?php

namespace Database\Seeders\Demo;

use App\Enums\AdhesionRegistrationStatus;
use App\Enums\ApplicationPreferenceSource;
use App\Enums\ApplicationStatus;
use App\Enums\HouseholdRelationship;
use App\Enums\HousingCompatibilityStatus;
use App\Enums\HousingCondition;
use App\Enums\HousingStatus;
use App\Enums\IncomeSourceType;
use App\Enums\ProfessionalStatus;
use App\Enums\SimulationResultStatus;
use App\Enums\SimulationScope;
use App\Enums\SimulationSessionStatus;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\CurrentHousingSituation;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HousingPreference;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use App\Models\SimulationRecommendedContest;
use App\Models\SimulationSession;
use App\Models\User;
use App\Services\Allocation\HousingPreferenceService;
use App\Services\Applications\ApplicationService;
use App\Services\Candidate\AdhesionRegistrationService;
use App\Services\Candidate\HouseholdMemberService;
use App\Services\Candidate\HouseholdService;
use App\Services\Candidate\HousingSituationService;
use App\Services\Candidate\IncomeService;
use App\Services\Simulator\AdvancedEligibilitySimulatorService;
use App\Support\Demo\MunicipalApplicationDemoContext;
use BackedEnum;
use Carbon\CarbonInterface;
use Database\Seeders\IncomeSourceSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class MunicipalApplicationDemoCandidateSeeder extends Seeder
{
    public const CANDIDATE_NIF = '299123456';

    public const SPOUSE_NIF = '299123464';

    public const CHILD_NIF = '299123472';

    public const APPLICATION_NOTE =
        'Candidatura fictícia preparada para demonstração do percurso municipal.';

    private const CANDIDATE_DOCUMENT = 'DEMO-CC-JMF-001';

    private const SPOUSE_DOCUMENT = 'DEMO-CC-ASF-001';

    private const CHILD_DOCUMENT = 'DEMO-CC-IF-001';

    public function run(): void
    {
        $context = app(MunicipalApplicationDemoContext::class);
        $context->assertSeederAllowed();

        $this->call(IncomeSourceSeeder::class);

        DB::transaction(function (): void {
            $candidate = $this->candidate();
            $contest = $this->contest();

            $registration = $this->ensureRegistration($candidate);
            $household = $this->ensureHousehold(
                $registration,
                $candidate,
            );

            $applicant = $this->ensureApplicant(
                $household,
                $registration,
                $candidate,
            );
            $spouse = $this->ensureSpouse(
                $household,
                $candidate,
            );
            $this->ensureChild(
                $household,
                $candidate,
            );

            $employment = IncomeSource::query()
                ->where('code', IncomeSourceType::Employment->value)
                ->where('is_active', true)
                ->firstOrFail();

            $this->ensureIncome(
                household: $household,
                member: $applicant,
                source: $employment,
                actor: $candidate,
                description: 'Remuneração mensal — João Miguel Ferreira',
                monthlyAmount: '1250.00',
                annualAmount: '17500.00',
            );
            $this->ensureIncome(
                household: $household,
                member: $spouse,
                source: $employment,
                actor: $candidate,
                description: 'Remuneração mensal — Ana Sofia Ferreira',
                monthlyAmount: '920.00',
                annualAmount: '12880.00',
            );

            $this->ensureHousingSituation(
                $registration,
                $candidate,
            );
            $registration = $this->ensureRegistered(
                $registration,
                $candidate,
            );

            $this->ensureSimulation(
                $candidate,
                $registration,
                $contest,
            );

            $application = $this->ensureApplication(
                $candidate,
                $contest,
                $registration,
                $household,
            );

            $this->ensurePreferences(
                $application,
                $candidate,
                $contest,
            );
        }, 3);
    }

    private function candidate(): User
    {
        return User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL,
            )
            ->whereHas(
                'municipality',
                static fn ($query) => $query->where(
                    'code',
                    MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
                ),
            )
            ->firstOrFail();
    }

    private function contest(): Contest
    {
        return Contest::query()
            ->where(
                'code',
                MunicipalApplicationDemoCatalogSeeder::CONTEST_CODE,
            )
            ->with('program')
            ->firstOrFail();
    }

    private function ensureRegistration(
        User $candidate,
    ): AdhesionRegistration {
        $data = [
            'full_name' => 'João Miguel Ferreira',
            'email' => MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL,
            'phone' => null,
            'mobile_phone' => '912345678',
            'document_type' => 'citizen_card',
            'document_number' => self::CANDIDATE_DOCUMENT,
            'document_valid_until' => '2030-12-31',
            'nif' => self::CANDIDATE_NIF,
            'birth_date' => '1990-03-15',
            'nationality' => 'Portuguesa',
            'address' => 'Rua das Oliveiras, n.º 25',
            'postal_code' => '2380-000',
            'city' => 'Alcanena',
            'parish' => 'Alcanena e Vila Moreira',
            'municipality' => 'Alcanena',
            'wants_email_notifications' => true,
            'wants_sms_notifications' => true,
            'wants_postal_notifications' => false,
            'accepts_terms' => true,
            'accepts_data_processing' => true,
        ];

        $registration = AdhesionRegistration::withTrashed()
            ->where('user_id', $candidate->id)
            ->first();

        if ($registration?->trashed()) {
            $registration->restore();
        }

        $service = app(AdhesionRegistrationService::class);

        if (! $registration instanceof AdhesionRegistration) {
            return $service->create($data, $candidate);
        }

        if (! $this->matches($registration, $data)) {
            return $service->update(
                $registration,
                $data,
                $candidate,
            );
        }

        return $registration;
    }

    private function ensureHousehold(
        AdhesionRegistration $registration,
        User $candidate,
    ): Household {
        $data = [
            'name' => 'Agregado de João Miguel Ferreira',
            'household_type' => 'family',
            'notes' => 'Dados integralmente fictícios de demonstração.',
        ];

        $household = Household::withTrashed()
            ->where(
                'adhesion_registration_id',
                $registration->id,
            )
            ->first();

        if ($household?->trashed()) {
            $household->restore();
        }

        $service = app(HouseholdService::class);

        if (! $household instanceof Household) {
            return $service->create(
                $registration,
                $data,
                $candidate,
            );
        }

        $this->assertMunicipalOwnership(
            $household->getAttribute('municipality_id'),
            $candidate,
            'agregado',
        );

        if (! $this->matches($household, $data)) {
            return $service->update(
                $household,
                $data,
                $candidate,
            );
        }

        return $household->fresh(['members', 'incomeRecords'])
            ?? $household;
    }

    private function ensureApplicant(
        Household $household,
        AdhesionRegistration $registration,
        User $candidate,
    ): HouseholdMember {
        $member = HouseholdMember::withTrashed()
            ->where('household_id', $household->id)
            ->where('is_applicant', true)
            ->first();

        if (! $member instanceof HouseholdMember) {
            app(HouseholdService::class)->syncApplicant(
                $household,
                $registration,
            );

            $member = HouseholdMember::query()
                ->where('household_id', $household->id)
                ->where('is_applicant', true)
                ->firstOrFail();
        }

        if ($member->trashed()) {
            $member->restore();
        }

        return $this->ensureMember(
            $household,
            $member,
            $this->applicantData(),
            $candidate,
        );
    }

    private function ensureSpouse(
        Household $household,
        User $candidate,
    ): HouseholdMember {
        $member = HouseholdMember::withTrashed()
            ->where('household_id', $household->id)
            ->where('nif', self::SPOUSE_NIF)
            ->first();

        return $this->ensureMember(
            $household,
            $member,
            $this->spouseData(),
            $candidate,
        );
    }

    private function ensureChild(
        Household $household,
        User $candidate,
    ): HouseholdMember {
        $member = HouseholdMember::withTrashed()
            ->where('household_id', $household->id)
            ->where('nif', self::CHILD_NIF)
            ->first();

        return $this->ensureMember(
            $household,
            $member,
            $this->childData(),
            $candidate,
        );
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    private function ensureMember(
        Household $household,
        ?HouseholdMember $member,
        array $data,
        User $candidate,
    ): HouseholdMember {
        $service = app(HouseholdMemberService::class);

        if (! $member instanceof HouseholdMember) {
            return $service->create(
                $household,
                $data,
                $candidate,
            );
        }

        if ($member->trashed()) {
            $member->restore();
        }

        if (
            $member->household_id !== $household->id
            || $member->adhesion_registration_id
                !== $household->adhesion_registration_id
        ) {
            throw new LogicException(
                'O membro demo está associado a outro agregado.',
            );
        }

        if (! $this->matches($member, $data)) {
            return $service->update(
                $member,
                $data,
                $candidate,
            );
        }

        return $member;
    }

    /**
     * @return array<string, bool|float|int|string|null>
     */
    private function applicantData(): array
    {
        return [
            'is_applicant' => true,
            'full_name' => 'João Miguel Ferreira',
            'birth_date' => '1990-03-15',
            'gender' => 'male',
            'relationship' => HouseholdRelationship::Applicant->value,
            'nationality' => 'Portuguesa',
            'document_type' => 'citizen_card',
            'document_number' => self::CANDIDATE_DOCUMENT,
            'document_valid_until' => '2030-12-31',
            'nif' => self::CANDIDATE_NIF,
            'marital_status' => 'married',
            'professional_status' => ProfessionalStatus::Employed->value,
            'qualification_level' => 4,
            'employment_type' => 'permanent',
            'employer_name' => 'Empresa Municipal Demo',
            'workplace_municipality' => 'Alcanena',
            'works_in_municipality' => true,
            'is_dependent' => false,
            'is_student' => false,
            'is_disabled' => false,
            'has_multiple_disabilities' => false,
            'is_pregnant' => false,
            'disability_percentage' => null,
            'has_reduced_mobility' => false,
            'is_informal_caregiver' => false,
            'has_no_income' => false,
            'is_exempt_from_irs' => false,
            'no_income_reason' => null,
            'notes' => 'Pessoa fictícia de demonstração.',
        ];
    }

    /**
     * @return array<string, bool|float|int|string|null>
     */
    private function spouseData(): array
    {
        return [
            'is_applicant' => false,
            'full_name' => 'Ana Sofia Ferreira',
            'birth_date' => '1992-11-08',
            'gender' => 'female',
            'relationship' => HouseholdRelationship::Spouse->value,
            'nationality' => 'Portuguesa',
            'document_type' => 'citizen_card',
            'document_number' => self::SPOUSE_DOCUMENT,
            'document_valid_until' => '2031-06-30',
            'nif' => self::SPOUSE_NIF,
            'marital_status' => 'married',
            'professional_status' => ProfessionalStatus::Employed->value,
            'qualification_level' => 4,
            'employment_type' => 'permanent',
            'employer_name' => 'Comércio Local Demo',
            'workplace_municipality' => 'Alcanena',
            'works_in_municipality' => true,
            'is_dependent' => false,
            'is_student' => false,
            'is_disabled' => false,
            'has_multiple_disabilities' => false,
            'is_pregnant' => false,
            'disability_percentage' => null,
            'has_reduced_mobility' => false,
            'is_informal_caregiver' => false,
            'has_no_income' => false,
            'is_exempt_from_irs' => false,
            'no_income_reason' => null,
            'notes' => 'Pessoa fictícia de demonstração.',
        ];
    }

    /**
     * @return array<string, bool|float|int|string|null>
     */
    private function childData(): array
    {
        return [
            'is_applicant' => false,
            'full_name' => 'Inês Ferreira',
            'birth_date' => '2019-05-20',
            'gender' => 'female',
            'relationship' => HouseholdRelationship::Child->value,
            'nationality' => 'Portuguesa',
            'document_type' => 'citizen_card',
            'document_number' => self::CHILD_DOCUMENT,
            'document_valid_until' => '2029-05-20',
            'nif' => self::CHILD_NIF,
            'marital_status' => 'single',
            'professional_status' => ProfessionalStatus::Student->value,
            'qualification_level' => null,
            'employment_type' => null,
            'employer_name' => null,
            'workplace_municipality' => null,
            'works_in_municipality' => false,
            'is_dependent' => true,
            'is_student' => true,
            'is_disabled' => false,
            'has_multiple_disabilities' => false,
            'is_pregnant' => false,
            'disability_percentage' => null,
            'has_reduced_mobility' => false,
            'is_informal_caregiver' => false,
            'has_no_income' => true,
            'is_exempt_from_irs' => true,
            'no_income_reason' => 'Menor dependente',
            'notes' => 'Pessoa fictícia de demonstração.',
        ];
    }

    private function ensureIncome(
        Household $household,
        HouseholdMember $member,
        IncomeSource $source,
        User $actor,
        string $description,
        string $monthlyAmount,
        string $annualAmount,
    ): IncomeRecord {
        $data = [
            'household_member_id' => $member->id,
            'income_source_id' => $source->id,
            'description' => $description,
            'monthly_amount' => $monthlyAmount,
            'annual_amount' => $annualAmount,
            'reference_year' => 2026,
            'starts_at' => '2026-01-01',
            'ends_at' => null,
            'is_current' => true,
            'is_taxable' => true,
            'notes' => 'Rendimento fictício de demonstração.',
        ];

        $record = IncomeRecord::withTrashed()
            ->where('household_member_id', $member->id)
            ->where('income_source_id', $source->id)
            ->where('description', $description)
            ->first();

        $service = app(IncomeService::class);

        if (! $record instanceof IncomeRecord) {
            return $service->create(
                $household,
                $member,
                $data,
                $actor,
            );
        }

        if ($record->trashed()) {
            $record->restore();
        }

        if (
            $record->household_id !== $household->id
            || $record->adhesion_registration_id
                !== $household->adhesion_registration_id
        ) {
            throw new LogicException(
                'O rendimento demo está associado a outro agregado.',
            );
        }

        if (! $this->matches($record, $data)) {
            return $service->update(
                $record,
                $data,
                $actor,
            );
        }

        return $record;
    }

    private function ensureHousingSituation(
        AdhesionRegistration $registration,
        User $candidate,
    ): CurrentHousingSituation {
        $data = [
            'housing_status' => HousingStatus::Rented->value,
            'current_address' => 'Rua do Mercado, n.º 10',
            'current_postal_code' => '2380-000',
            'current_city' => 'Alcanena',
            'current_parish' => 'Alcanena e Vila Moreira',
            'current_municipality' => 'Alcanena',
            'resides_in_municipality' => true,
            'residence_years_in_municipality' => '6.00',
            'works_in_municipality' => true,
            'workplace_municipality' => 'Alcanena',
            'current_housing_typology' => 'T2',
            'current_housing_rooms' => 2,
            'current_housing_condition' => HousingCondition::Adequate->value,
            'current_monthly_rent' => '650.00',
            'current_housing_expense' => '75.00',
            'is_overcrowded' => false,
            'is_at_risk_of_eviction' => false,
            'is_homeless' => false,
            'is_temporary_accommodation' => false,
            'is_domestic_violence_victim' => false,
            'has_accessibility_needs' => false,
            'has_high_rent_burden' => false,
            'request_reason' => 'Procura de renda compatível com o rendimento familiar.',
            'additional_notes' => 'Dados fictícios de demonstração.',
        ];

        $situation = CurrentHousingSituation::withTrashed()
            ->where(
                'adhesion_registration_id',
                $registration->id,
            )
            ->first();

        if ($situation?->trashed()) {
            $situation->restore();
        }

        if (
            $situation instanceof CurrentHousingSituation
            && $this->matches($situation, $data)
        ) {
            return $situation;
        }

        return app(HousingSituationService::class)
            ->updateOrCreate(
                $registration,
                $data,
                $candidate,
            );
    }

    private function ensureRegistered(
        AdhesionRegistration $registration,
        User $candidate,
    ): AdhesionRegistration {
        $registration->refresh();

        if (
            $registration->status
            === AdhesionRegistrationStatus::Registered
        ) {
            return $registration;
        }

        if (
            $registration->status
            !== AdhesionRegistrationStatus::Incomplete
        ) {
            throw ValidationException::withMessages([
                'registration' => 'O Registo de Adesão demo não pode ser '
                    .'finalizado a partir do estado atual.',
            ]);
        }

        return app(AdhesionRegistrationService::class)
            ->finalize(
                $registration,
                $candidate,
            );
    }

    private function ensureSimulation(
        User $candidate,
        AdhesionRegistration $registration,
        Contest $contest,
    ): SimulationSession {
        $sessions = SimulationSession::withTrashed()
            ->where('user_id', $candidate->id)
            ->where(
                'adhesion_registration_id',
                $registration->id,
            )
            ->where('scope', SimulationScope::Authenticated->value)
            ->get();

        if ($sessions->count() > 1) {
            throw new LogicException(
                'Existem múltiplas simulações autenticadas para o candidato demo.',
            );
        }

        $session = $sessions->first();

        if ($session?->trashed()) {
            throw new LogicException(
                'A simulação demo existente encontra-se eliminada.',
            );
        }

        if ($session instanceof SimulationSession) {
            $session->loadMissing([
                'result',
                'recommendedContests',
            ]);

            $recommended = $session->recommendedContests
                ->contains(
                    static fn (
                        SimulationRecommendedContest $item,
                    ): bool => $item->contest_id === $contest->id,
                );

            if (
                $session->status
                    !== SimulationSessionStatus::Completed
                || $session->result_status
                    !== SimulationResultStatus::LikelyEligible
                || ! $recommended
            ) {
                throw new LogicException(
                    'A simulação demo existente não corresponde ao cenário esperado.',
                );
            }

            return $session;
        }

        $request = Request::create(
            '/simulador/demo',
            'POST',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'MV-HAB municipal demo seeder',
            ],
        );
        $request->setUserResolver(
            static fn (): User => $candidate,
        );

        return app(AdvancedEligibilitySimulatorService::class)
            ->simulateForUser(
                $candidate,
                [
                    'contest_id' => $contest->id,
                    'preferred_typologies' => ['T2'],
                    'preferred_parishes' => [
                        'Alcanena e Vila Moreira',
                    ],
                    'has_property' => false,
                    'receives_housing_support' => false,
                    'has_municipal_debt' => false,
                    'tax_regularized' => true,
                    'social_security_regularized' => true,
                    'has_residence_permit' => true,
                    'false_declarations_history' => false,
                    'previous_municipal_eviction' => false,
                ],
                $request,
            );
    }

    private function ensureApplication(
        User $candidate,
        Contest $contest,
        AdhesionRegistration $registration,
        Household $household,
    ): Application {
        $applications = Application::withTrashed()
            ->where('user_id', $candidate->id)
            ->where('contest_id', $contest->id)
            ->get();

        if ($applications->count() > 1) {
            throw new LogicException(
                'Existem múltiplas candidaturas demo para o mesmo concurso.',
            );
        }

        $application = $applications->first();

        if ($application?->trashed()) {
            throw new LogicException(
                'A candidatura demo existente encontra-se eliminada.',
            );
        }

        if (! $application instanceof Application) {
            return app(ApplicationService::class)
                ->createDraft(
                    $candidate,
                    $contest,
                    [
                        'candidate_notes' => self::APPLICATION_NOTE,
                    ],
                );
        }

        if ($application->status !== ApplicationStatus::Draft) {
            throw new LogicException(
                'A Sprint 51D.1 exige uma candidatura demo em rascunho.',
            );
        }

        $situation = $registration->currentHousingSituation()
            ->firstOrFail();

        if (
            $application->adhesion_registration_id !== $registration->id
            || $application->program_id !== $contest->program_id
            || $application->household_id !== $household->id
            || $application->current_housing_situation_id
                !== $situation->id
        ) {
            throw new LogicException(
                'A candidatura demo existente possui associações incompatíveis.',
            );
        }

        if (
            $application->candidate_notes
            !== self::APPLICATION_NOTE
        ) {
            return app(ApplicationService::class)
                ->updateDraft(
                    $application,
                    [
                        'candidate_notes' => self::APPLICATION_NOTE,
                    ],
                    $candidate,
                );
        }

        return $application;
    }

    private function ensurePreferences(
        Application $application,
        User $candidate,
        Contest $contest,
    ): void {
        $units = ContestHousingUnit::query()
            ->where('contest_id', $contest->id)
            ->with('housingUnit')
            ->get()
            ->sortBy(
                static fn (ContestHousingUnit $unit): string => (string) $unit->housingUnit->code,
            )
            ->values();

        if ($units->count() !== 3) {
            throw new LogicException(
                'O concurso demo deve disponibilizar exatamente três fogos.',
            );
        }

        /** @var list<array<string, mixed>> $payload */
        $payload = array_values(
            $units
                ->map(
                    static fn (
                        ContestHousingUnit $unit,
                        int $index,
                    ): array => [
                        'contest_housing_unit_id' => $unit->id,
                        'preference_order' => $index + 1,
                        'notes' => null,
                    ],
                )
                ->all(),
        );

        $existing = HousingPreference::withTrashed()
            ->where('application_id', $application->id)
            ->orderBy('preference_order')
            ->get();

        $expectedUnitIds = $units->pluck('id')->all();
        $existingUnitIds = $existing
            ->pluck('contest_housing_unit_id')
            ->all();
        $existingOrders = $existing
            ->pluck('preference_order')
            ->map(static fn ($order): int => (int) $order)
            ->all();

        $freshApplication = $application->fresh();
        $source = $freshApplication instanceof Application
            ? $freshApplication->preference_source
            : null;
        $alreadyConfigured = $existing->count() === 3
            && $existing->whereNotNull('deleted_at')->isEmpty()
            && $existingUnitIds === $expectedUnitIds
            && $existingOrders === [1, 2, 3]
            && $existing->every(
                static fn (
                    HousingPreference $preference,
                ): bool => $preference->compatibility_status
                    === HousingCompatibilityStatus::Compatible
                    && $preference->invalidated_at === null
                    && $preference->submitted_at === null
                    && $preference->locked_at === null,
            )
            && $source instanceof ApplicationPreferenceSource
            && $source->isOfficial();

        if ($alreadyConfigured) {
            return;
        }

        app(HousingPreferenceService::class)->replace(
            $application,
            $payload,
            $candidate,
            false,
        );
    }

    private function assertMunicipalOwnership(
        mixed $municipalityId,
        User $candidate,
        string $subject,
    ): void {
        if ((int) $municipalityId !== (int) $candidate->municipality_id) {
            throw new LogicException(
                "O {$subject} demo pertence a outro Município.",
            );
        }
    }

    /**
     * @param  array<string, mixed>  $expected
     */
    private function matches(
        Model $model,
        array $expected,
    ): bool {
        foreach ($expected as $attribute => $expectedValue) {
            if (
                $attribute === 'household_member_id'
                && ! $model->isFillable($attribute)
            ) {
                continue;
            }

            $actualValue = $this->normalizeValue(
                $model->getAttribute($attribute),
            );
            $normalizedExpected = $this->normalizeValue(
                $expectedValue,
            );

            if ($actualValue !== $normalizedExpected) {
                return false;
            }
        }

        return true;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format(
                $value->format('H:i:s') === '00:00:00'
                    ? 'Y-m-d'
                    : 'Y-m-d H:i:s',
            );
        }

        if (is_bool($value) || $value === null) {
            return $value;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return number_format($value, 2, '.', '');
        }

        return is_scalar($value) ? (string) $value : $value;
    }
}
