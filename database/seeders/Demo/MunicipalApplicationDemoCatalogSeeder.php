<?php

namespace Database\Seeders\Demo;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\AllocationMethod;
use App\Enums\AllocationRuleSetStatus;
use App\Enums\ContestDeadlineType;
use App\Enums\ContestHousingUnitStatus;
use App\Enums\ContestStatus;
use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentCategory;
use App\Enums\DocumentReferencePeriodUnit;
use App\Enums\EligibilityCriterionCategory;
use App\Enums\EligibilityOperator;
use App\Enums\EligibilityRuleSetStatus;
use App\Enums\HousingLocationPrecision;
use App\Enums\HousingPublicStatus;
use App\Enums\HousingUnitStatus;
use App\Enums\IncomeSourceType;
use App\Enums\ProgramStatus;
use App\Enums\PublicVisibilityStatus;
use App\Enums\RegulatoryConfigurationStatus;
use App\Enums\RegulatoryContext;
use App\Enums\RegulatoryProfileStatus;
use App\Enums\RentCalculationMethod;
use App\Enums\RentLimitConfigurationStatus;
use App\Enums\RentRuleSetStatus;
use App\Enums\RequiredDocumentConditionOperator;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\AllocationRuleSet;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\DocumentType;
use App\Models\EligibilityRuleSet;
use App\Models\HousingUnit;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\RentLimitTableManifest;
use App\Models\RentLimitTableRow;
use App\Models\RentRuleSet;
use App\Models\RequiredDocument;
use App\Models\TypologyAdequacyRule;
use App\Models\User;
use App\Services\Regulatory\MunicipalRegulatoryOverlayService;
use App\Services\Regulatory\RegulatorySnapshotService;
use App\Services\Regulatory\RentLimits\RentLimitTableChecksumService;
use App\Support\Demo\MunicipalApplicationDemoContext;
use Carbon\CarbonImmutable;
use Database\Seeders\AffordableRentRegulatoryProfileSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

final class MunicipalApplicationDemoCatalogSeeder extends Seeder
{
    public const PROFILE_CODE = 'ALCANENA-DEMO-PAA-2026';

    public const PROGRAM_SLUG =
        'programa-demo-candidaturas-arrendamento-acessivel-alcanena';

    public const CONTEST_CODE = 'ALC-DEMO-CAND-01-2026';

    public const SNAPSHOT_ORIGIN =
        'municipal_application_demo_catalog_seeder';

    private const PROFILE_VERSION = '2026.1-demo-applications';

    private const PROGRAM_NAME =
        'Programa Municipal de Arrendamento Acessível — Demonstração';

    private const CONTEST_TITLE =
        'Concurso de demonstração — Candidaturas e visitas';

    public function run(): void
    {
        $context = app(MunicipalApplicationDemoContext::class);
        $context->assertSeederAllowed();

        $this->call([
            AffordableRentRegulatoryProfileSeeder::class,
            DocumentTypeSeeder::class,
        ]);

        DB::transaction(function () use ($context): void {
            $referenceDate = $context->referenceDate();
            $municipality = $this->demoMunicipality();
            $actor = $this->demoAnalyst($municipality);
            $profile = $this->seedRegulatoryProfile(
                $municipality,
                $actor,
            );

            [$program, $contest] = $this->seedProgramAndContest(
                $municipality,
                $profile,
                $actor,
                $referenceDate,
            );

            $this->seedProgramRules($program, $referenceDate);
            $this->seedContestDeadlines($contest, $referenceDate);
            $this->seedRequiredDocuments($program, $contest);
            $this->seedEligibility(
                $program,
                $contest,
                $profile,
                $actor,
            );
            $this->seedHousingCatalogue(
                $municipality,
                $program,
                $contest,
                $profile,
                $actor,
            );
            $this->seedAllocationRules(
                $program,
                $contest,
                $profile,
                $actor,
            );
            $this->seedRentRules(
                $program,
                $contest,
                $profile,
                $actor,
                $referenceDate,
            );
            $this->seedRegulatorySnapshots(
                $program,
                $contest,
                $profile,
                $actor,
            );
        });
    }

    private function demoMunicipality(): Municipality
    {
        return Municipality::query()
            ->where(
                'code',
                MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
            )
            ->firstOrFail();
    }

    private function demoAnalyst(Municipality $municipality): User
    {
        return User::query()
            ->where('municipality_id', $municipality->id)
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::ANALYST_EMAIL,
            )
            ->firstOrFail();
    }

    private function seedRegulatoryProfile(
        Municipality $municipality,
        User $actor,
    ): AffordableRentRegulatoryProfile {
        $nationalPaa = AffordableRentRegulatoryProfile::query()
            ->where(
                'code',
                AffordableRentRegulatoryProfileSeeder::PAA_NATIONAL_CODE,
            )
            ->firstOrFail();

        $profile = AffordableRentRegulatoryProfile::withTrashed()
            ->firstOrNew([
                'code' => self::PROFILE_CODE,
                'version' => self::PROFILE_VERSION,
            ]);

        $this->assertCompatibleMunicipality(
            $profile->municipality_id,
            $municipality,
            'perfil regulamentar',
        );

        $profile->forceFill([
            'municipality_id' => $municipality->id,
            'parent_profile_id' => $nationalPaa->id,
            'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019,
            'name' => 'Overlay municipal PAA — demonstração de candidaturas',
            'legal_basis' => 'Configuração exclusivamente fictícia para '
                .'demonstração funcional da plataforma MV-HAB.',
            'effective_from' => CarbonImmutable::create(
                2026,
                1,
                1,
                timezone: 'Europe/Lisbon',
            ),
            'effective_until' => CarbonImmutable::create(
                2026,
                8,
                31,
                timezone: 'Europe/Lisbon',
            ),
            'status' => RegulatoryProfileStatus::Active,
            'configuration_status' => RegulatoryConfigurationStatus::Complete,
            'official_source' => 'Dados fictícios de demonstração; '
                .'não constituem fonte regulamentar oficial.',
            'publication_reference' => 'DEMO-ALCANENA-CANDIDATURAS-2026-SEM-VALOR-JURIDICO',
            'source_version' => self::PROFILE_VERSION,
            'maximum_effort_rate_percentage' => '35.00',
            'minimum_adult_monthly_income' => '920.00',
            'annual_income_base_limit' => null,
            'second_person_increment' => null,
            'additional_person_increment' => null,
            'tax_year' => 2026,
            'sixth_irs_bracket_upper_limit' => '999999.00',
            'irs_source_reference' => 'DEMO-IRS-2026-SEM-VALOR-JURIDICO',
            'irs_source_version' => 'demo-fixture-2026.1',
            'irs_effective_from' => CarbonImmutable::create(
                2026,
                1,
                1,
                timezone: 'Europe/Lisbon',
            ),
            'irs_effective_until' => CarbonImmutable::create(
                2026,
                12,
                31,
                timezone: 'Europe/Lisbon',
            ),
            'minimum_contract_months' => 60,
            'standard_contract_months' => 60,
            'rent_limits_configured' => true,
            'eligibility_rules_configured' => true,
            'typology_rules_configured' => true,
            'contract_terms_configured' => true,
            'metadata' => [
                'catalogue_type' => 'municipal_overlay',
                'demo' => true,
                'demo_only' => true,
                'demo_scope' => 'municipal_application',
                'administrative_effects' => false,
                'contract_configuration_in_scope' => false,
            ],
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
            'deleted_at' => null,
        ])->save();

        $profile->refresh();

        app(MunicipalRegulatoryOverlayService::class)
            ->assertValid($profile);

        return $profile;
    }

    /**
     * @return array{Program, Contest}
     */
    private function seedProgramAndContest(
        Municipality $municipality,
        AffordableRentRegulatoryProfile $profile,
        User $actor,
        CarbonImmutable $referenceDate,
    ): array {
        $programStartsAt = $referenceDate->subDays(30);
        $programPublishedAt = $referenceDate
            ->subDays(31)
            ->setTime(9, 0);
        $contestPublishedAt = $referenceDate
            ->subDays(8)
            ->setTime(9, 0);
        $contestOpensAt = $referenceDate
            ->subDays(7)
            ->setTime(9, 0);
        $contestClosesAt = $referenceDate
            ->addDays(90)
            ->setTime(17, 0);

        $program = Program::withTrashed()->firstOrNew([
            'slug' => self::PROGRAM_SLUG,
        ]);

        $this->assertCompatibleMunicipality(
            $program->municipality_id,
            $municipality,
            'programa',
        );

        $program->forceFill([
            'municipality_id' => $municipality->id,
            'regulatory_profile_id' => $profile->id,
            'legal_regime' => $profile->legal_regime,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
            'name' => self::PROGRAM_NAME,
            'summary' => 'Programa fictício para demonstrar registo, '
                .'candidatura, análise documental, visitas e exportação.',
            'description' => 'Programa municipal de demonstração com dados '
                .'inteiramente fictícios e sem efeitos administrativos.',
            'legal_basis' => 'Configuração demo baseada no contexto funcional '
                .'do arrendamento acessível municipal; validar todas as fontes '
                .'antes de qualquer utilização real.',
            'status' => ProgramStatus::Published,
            'starts_at' => $programStartsAt,
            'ends_at' => null,
            'published_at' => $programPublishedAt,
            'deleted_at' => null,
        ])->save();

        $program->refresh();

        $contest = Contest::withTrashed()->firstOrNew([
            'code' => self::CONTEST_CODE,
        ]);

        if (
            $contest->exists
            && (int) $contest->program_id !== (int) $program->id
        ) {
            throw new LogicException(
                'O concurso demo já está associado a outro programa.',
            );
        }

        $contest->forceFill([
            'program_id' => $program->id,
            'regulatory_profile_id' => $profile->id,
            'legal_regime' => $profile->legal_regime,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
            'slug' => 'concurso-demo-candidaturas-alcanena-2026',
            'title' => self::CONTEST_TITLE,
            'summary' => 'Concurso fictício com três habitações T2 '
                .'para demonstrar preferências e processamento municipal.',
            'description' => 'Todos os dados, prazos, rendas, moradas e '
                .'parâmetros são fictícios e destinam-se apenas à demo.',
            'application_instructions' => 'Concluir o registo, preencher o '
                .'agregado, carregar a documentação e ordenar até três '
                .'habitações T2 por preferência.',
            'status' => ContestStatus::Published,
            'opens_at' => $contestOpensAt,
            'closes_at' => $contestClosesAt,
            'published_at' => $contestPublishedAt,
            'deleted_at' => null,
        ])->save();

        return [$program, $contest->refresh()];
    }

    private function seedProgramRules(
        Program $program,
        CarbonImmutable $referenceDate,
    ): void {
        $rules = [
            [
                'Demonstração sem efeitos administrativos',
                'Todos os dados e resultados são fictícios e não produzem '
                    .'qualquer decisão administrativa.',
                10,
            ],
            [
                'Registo prévio',
                'O candidato deve concluir o Registo de Adesão antes da '
                    .'candidatura.',
                20,
            ],
            [
                'Preferências habitacionais',
                'A candidatura deve indicar entre uma e três habitações '
                    .'compatíveis por ordem de preferência.',
                30,
            ],
            [
                'Taxa de esforço demo',
                'A configuração fictícia aplica uma taxa de esforço máxima '
                    .'de 35%.',
                40,
            ],
        ];

        foreach ($rules as [$title, $description, $sortOrder]) {
            $program->rules()->updateOrCreate(
                ['title' => $title],
                [
                    'description' => $description,
                    'sort_order' => $sortOrder,
                    'effective_from' => $referenceDate->subDays(30),
                    'effective_until' => null,
                ],
            );
        }
    }

    private function seedContestDeadlines(
        Contest $contest,
        CarbonImmutable $referenceDate,
    ): void {
        $deadlines = [
            [
                ContestDeadlineType::Applications,
                'Período de candidaturas',
                $contest->opens_at,
                $contest->closes_at,
                10,
            ],
            [
                ContestDeadlineType::Corrections,
                'Aperfeiçoamento documental',
                $referenceDate->addDays(30)->setTime(9, 0),
                $referenceDate->addDays(40)->setTime(17, 0),
                20,
            ],
        ];

        foreach (
            $deadlines as [$type, $label, $startsAt, $endsAt, $sortOrder]
        ) {
            $contest->deadlines()->updateOrCreate(
                [
                    'type' => $type->value,
                    'label' => $label,
                ],
                [
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'description' => 'Prazo fictício de demonstração; '
                        .'validar no edital municipal antes de produção.',
                    'sort_order' => $sortOrder,
                ],
            );
        }
    }

    private function seedRequiredDocuments(
        Program $program,
        Contest $contest,
    ): void {
        $definitions = [
            [
                'alcanena_demo_identificacao_residencia',
                'Identificação civil ou autorização de residência',
                DocumentCategory::Identification,
                DocumentAppliesTo::HouseholdMember,
            ],
            [
                'alcanena_demo_nif',
                'Cartão ou comprovativo de NIF',
                DocumentCategory::Tax,
                DocumentAppliesTo::HouseholdMember,
            ],
            [
                'alcanena_demo_nota_liquidacao_irs',
                'Nota de liquidação de IRS do ano fiscal anterior',
                DocumentCategory::Income,
                DocumentAppliesTo::Household,
            ],
            [
                'alcanena_demo_situacao_regular_at',
                'Certidão de situação regularizada na AT',
                DocumentCategory::Tax,
                DocumentAppliesTo::AdhesionRegistration,
            ],
            [
                'alcanena_demo_situacao_regular_iss',
                'Certidão de situação regularizada no ISS',
                DocumentCategory::SocialSecurity,
                DocumentAppliesTo::AdhesionRegistration,
            ],
        ];

        foreach (
            $definitions as $index => [$code, $name, $category, $appliesTo]
        ) {
            $type = DocumentType::withTrashed()->firstOrNew([
                'code' => $code,
            ]);
            $type->forceFill([
                'name' => $name,
                'description' => 'Documento fictício da checklist mínima '
                    .'da demonstração municipal.',
                'category' => $category,
                'applies_to' => $appliesTo,
                'is_active' => true,
                'is_required_by_default' => false,
                'requires_expiry_date' => false,
                'requires_issue_date' => false,
                'allowed_mime_types' => [
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ],
                'max_file_size_mb' => 10,
                'sort_order' => ($index + 1) * 10,
                'deleted_at' => null,
            ])->save();
        }

        $requirements = [
            [
                'alcanena_demo_identificacao_residencia',
                DocumentAppliesTo::HouseholdMember,
                'always',
                RequiredDocumentConditionOperator::Always,
                null,
                'Documento de identificação ou residência válido.',
            ],
            [
                'alcanena_demo_nif',
                DocumentAppliesTo::HouseholdMember,
                'always',
                RequiredDocumentConditionOperator::Always,
                null,
                'Comprovativo de NIF de cada elemento aplicável.',
            ],
            [
                'alcanena_demo_nota_liquidacao_irs',
                DocumentAppliesTo::Household,
                'always',
                RequiredDocumentConditionOperator::Always,
                null,
                'Nota de liquidação de IRS do agregado.',
            ],
            [
                'alcanena_demo_situacao_regular_at',
                DocumentAppliesTo::AdhesionRegistration,
                'always',
                RequiredDocumentConditionOperator::Always,
                null,
                'Certidão de situação tributária regularizada.',
            ],
            [
                'alcanena_demo_situacao_regular_iss',
                DocumentAppliesTo::AdhesionRegistration,
                'always',
                RequiredDocumentConditionOperator::Always,
                null,
                'Certidão de situação contributiva regularizada.',
            ],
            [
                'recibos_vencimento',
                DocumentAppliesTo::IncomeRecord,
                'income_record.income_source',
                RequiredDocumentConditionOperator::Equals,
                IncomeSourceType::Employment->value,
                'Submeter os três recibos de vencimento mais recentes, '
                    .'relativos a meses distintos.',
            ],
        ];

        foreach (
            $requirements as $index => [
                $code,
                $requiredFor,
                $conditionKey,
                $operator,
                $conditionValue,
                $instructions,
            ]
        ) {
            $documentType = DocumentType::query()
                ->where('code', $code)
                ->firstOrFail();
            $repeatable = $code === 'recibos_vencimento';
            $required = RequiredDocument::withTrashed()->firstOrNew([
                'document_type_id' => $documentType->id,
                'program_id' => $program->id,
                'contest_id' => $contest->id,
                'required_for' => $requiredFor->value,
                'condition_key' => $conditionKey,
                'condition_operator' => $operator->value,
                'condition_value' => $conditionValue,
            ]);
            $required->forceFill([
                'is_required' => true,
                'is_active' => true,
                'required_submissions' => $repeatable ? 3 : 1,
                'reference_period_unit' => $repeatable
                    ? DocumentReferencePeriodUnit::Month->value
                    : null,
                'requires_distinct_reference_periods' => $repeatable,
                'reference_period_recency' => $repeatable ? 3 : null,
                'instructions' => $instructions,
                'sort_order' => ($index + 1) * 10,
                'deleted_at' => null,
            ])->save();
        }
    }

    private function seedEligibility(
        Program $program,
        Contest $contest,
        AffordableRentRegulatoryProfile $profile,
        User $actor,
    ): void {
        $ruleSet = EligibilityRuleSet::withTrashed()->firstOrNew([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'name' => 'Elegibilidade mínima — demonstração de candidaturas',
        ]);
        $ruleSet->forceFill([
            'regulatory_profile_id' => $profile->id,
            'description' => 'Critérios mínimos necessários ao percurso '
                .'demonstrado, sem decisão administrativa real.',
            'status' => EligibilityRuleSetStatus::Active,
            'is_default' => false,
            'starts_at' => $contest->opens_at,
            'ends_at' => $contest->closes_at,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
            'deleted_at' => null,
        ])->save();

        $criteria = [
            $this->eligibilityCriterion(
                'registration_is_registered',
                'Registo de Adesão finalizado',
                EligibilityCriterionCategory::Identity,
                EligibilityOperator::IsTrue,
                10,
            ),
            $this->eligibilityCriterion(
                'candidate_is_adult',
                'Idade mínima de 18 anos',
                EligibilityCriterionCategory::Identity,
                EligibilityOperator::IsTrue,
                20,
            ),
            $this->eligibilityCriterion(
                'all_household_members_have_valid_residency',
                'Residência válida dos elementos do agregado',
                EligibilityCriterionCategory::Residence,
                EligibilityOperator::IsTrue,
                30,
            ),
            $this->eligibilityCriterion(
                'has_household',
                'Agregado familiar preenchido',
                EligibilityCriterionCategory::Household,
                EligibilityOperator::IsTrue,
                40,
            ),
            $this->eligibilityCriterion(
                'has_applicant_member',
                'Requerente identificado no agregado',
                EligibilityCriterionCategory::Household,
                EligibilityOperator::IsTrue,
                50,
            ),
            $this->eligibilityCriterion(
                'has_income_information',
                'Informação de rendimentos completa',
                EligibilityCriterionCategory::Income,
                EligibilityOperator::IsTrue,
                60,
            ),
            $this->eligibilityCriterion(
                'has_current_housing_situation',
                'Situação habitacional preenchida',
                EligibilityCriterionCategory::Housing,
                EligibilityOperator::IsTrue,
                70,
            ),
            $this->eligibilityCriterion(
                'has_required_documents_submitted',
                'Documentos obrigatórios submetidos',
                EligibilityCriterionCategory::Documents,
                EligibilityOperator::AllRequiredDocumentsSubmitted,
                80,
            ),
            $this->eligibilityCriterion(
                'contest_is_open',
                'Concurso publicado e aberto',
                EligibilityCriterionCategory::Application,
                EligibilityOperator::IsTrue,
                90,
            ),
            $this->eligibilityCriterion(
                'no_duplicate_active_application',
                'Sem candidatura ativa duplicada',
                EligibilityCriterionCategory::Application,
                EligibilityOperator::IsTrue,
                100,
            ),
            $this->eligibilityCriterion(
                'typology_is_adequate',
                'Composição adequada à tipologia T2',
                EligibilityCriterionCategory::Typology,
                EligibilityOperator::IsTrue,
                110,
            ),
            $this->eligibilityCriterion(
                'rent_effort_within_35_percent',
                'Taxa de esforço máxima de 35%',
                EligibilityCriterionCategory::Income,
                EligibilityOperator::IsTrue,
                120,
                ['maximum_percentage' => '35.00'],
            ),
        ];

        $codes = [];

        foreach ($criteria as $criterion) {
            $codes[] = (string) $criterion['code'];
            $model = $ruleSet->criteria()
                ->withTrashed()
                ->firstOrNew(['code' => $criterion['code']]);
            $model->forceFill([
                ...$criterion,
                'deleted_at' => null,
            ])->save();
        }

        $ruleSet->criteria()
            ->whereNotIn('code', $codes)
            ->delete();
    }

    /**
     * @param  array<string, bool|float|int|string|null>|null  $expected
     * @return array<string, mixed>
     */
    private function eligibilityCriterion(
        string $code,
        string $name,
        EligibilityCriterionCategory $category,
        EligibilityOperator $operator,
        int $sortOrder,
        ?array $expected = null,
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'description' => 'Critério fictício do cenário municipal demo.',
            'category' => $category,
            'target' => 'calculated_value',
            'operator' => $operator,
            'expected_value' => $expected,
            'minimum_value' => null,
            'maximum_value' => null,
            'unit' => null,
            'is_mandatory' => true,
            'requires_manual_review' => false,
            'failure_message' => 'O requisito de demonstração não está '
                .'cumprido.',
            'success_message' => 'Requisito de demonstração cumprido.',
            'review_message' => 'Validar o requisito no backoffice demo.',
            'sort_order' => $sortOrder,
            'is_active' => true,
        ];
    }

    private function seedHousingCatalogue(
        Municipality $municipality,
        Program $program,
        Contest $contest,
        AffordableRentRegulatoryProfile $profile,
        User $actor,
    ): void {
        $units = [
            [
                'code' => 'ALC-DEMO-APP-T2-01',
                'reference' => 'ALC-DEMO-T2-CENTRO',
                'title' => 'T2 Centro de Alcanena — Demo',
                'slug' => 't2-centro-alcanena-demo-candidaturas',
                'rent' => '390.00',
                'address' => 'Morada fictícia A — Alcanena',
                'parish' => 'Alcanena e Vila Moreira',
                'locality' => 'Alcanena',
                'floor' => 'R/C',
                'gross_area' => '78.00',
                'usable_area' => '66.00',
                'energy_rating' => 'B',
                'latitude' => '39.4595000',
                'longitude' => '-8.6674000',
                'sort_order' => 10,
            ],
            [
                'code' => 'ALC-DEMO-APP-T2-02',
                'reference' => 'ALC-DEMO-T2-MONSANTO',
                'title' => 'T2 Monsanto — Demo',
                'slug' => 't2-monsanto-demo-candidaturas',
                'rent' => '400.00',
                'address' => 'Morada fictícia B — Monsanto',
                'parish' => 'Monsanto',
                'locality' => 'Monsanto',
                'floor' => '1.º',
                'gross_area' => '80.00',
                'usable_area' => '68.00',
                'energy_rating' => 'C',
                'latitude' => '39.4529000',
                'longitude' => '-8.7122000',
                'sort_order' => 20,
            ],
            [
                'code' => 'ALC-DEMO-APP-T2-03',
                'reference' => 'ALC-DEMO-T2-MINDE',
                'title' => 'T2 Minde — Demo',
                'slug' => 't2-minde-demo-candidaturas',
                'rent' => '410.00',
                'address' => 'Morada fictícia C — Minde',
                'parish' => 'Minde',
                'locality' => 'Minde',
                'floor' => '2.º',
                'gross_area' => '82.00',
                'usable_area' => '70.00',
                'energy_rating' => 'B-',
                'latitude' => '39.5162000',
                'longitude' => '-8.6885000',
                'sort_order' => 30,
            ],
        ];

        foreach ($units as $unit) {
            $housingUnit = HousingUnit::query()->firstOrNew([
                'code' => $unit['code'],
            ]);

            $this->assertCompatibleMunicipality(
                $housingUnit->municipality_id,
                $municipality,
                "habitação {$unit['code']}",
            );

            $housingUnit->forceFill([
                'municipality_id' => $municipality->id,
                'address' => $unit['address'],
                'typology' => 'T2',
                'bedrooms' => 2,
                'monthly_rent' => $unit['rent'],
                'status' => HousingUnitStatus::Available,
                'public_reference' => $unit['reference'],
                'public_title' => $unit['title'],
                'public_slug' => $unit['slug'],
                'public_summary' => 'Habitação T2 fictícia para '
                    .'demonstração do percurso de candidatura.',
                'public_description' => 'Imóvel inteiramente fictício. '
                    .'A morada, renda e características não correspondem '
                    .'a uma oferta municipal real.',
                'parish' => $unit['parish'],
                'locality' => $unit['locality'],
                'postal_code' => '2380-000',
                'floor' => $unit['floor'],
                'gross_area_sqm' => $unit['gross_area'],
                'usable_area_sqm' => $unit['usable_area'],
                'energy_rating' => $unit['energy_rating'],
                'public_location_description' => $unit['locality'],
                'public_address_visible' => false,
                'public_latitude' => $unit['latitude'],
                'public_longitude' => $unit['longitude'],
                'public_location_precision' => HousingLocationPrecision::Approximate,
                'public_status' => HousingPublicStatus::Available,
                'public_visibility_status' => PublicVisibilityStatus::Published,
                'is_public' => true,
                'published_at' => $contest->published_at,
                'unpublished_at' => null,
                'public_sort_order' => $unit['sort_order'],
                'seo_title' => $unit['title'],
                'seo_description' => 'Habitação fictícia de demonstração '
                    .'sem efeitos administrativos.',
            ])->save();

            $contestUnit = ContestHousingUnit::withTrashed()
                ->firstOrNew([
                    'contest_id' => $contest->id,
                    'housing_unit_id' => $housingUnit->id,
                ]);
            $contestUnit->forceFill([
                'program_id' => $program->id,
                'status' => ContestHousingUnitStatus::Available,
                'availability_starts_at' => $contest->opens_at,
                'availability_ends_at' => $contest->closes_at,
                'typology' => 'T2',
                'bedrooms' => 2,
                'min_occupants' => 2,
                'max_occupants' => 4,
                'accessible' => false,
                'reserved_for_special_condition' => null,
                'monthly_rent' => $unit['rent'],
                'estimated_expenses' => '45.00',
                'notes' => 'Habitação fictícia para demonstração.',
                'internal_notes' => 'Sem valor jurídico ou contratual.',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'deleted_at' => null,
            ])->save();
        }

        $typology = TypologyAdequacyRule::withTrashed()
            ->firstOrNew([
                'contest_id' => $contest->id,
                'name' => 'Adequação T2 — demonstração de candidaturas',
            ]);
        $typology->forceFill([
            'program_id' => $program->id,
            'regulatory_profile_id' => $profile->id,
            'description' => 'Regra demo para agregados de duas a quatro '
                .'pessoas e habitações T2.',
            'is_active' => true,
            'min_household_members' => 2,
            'max_household_members' => 4,
            'min_adults' => null,
            'max_adults' => null,
            'min_children' => null,
            'max_children' => null,
            'min_bedrooms' => 2,
            'max_bedrooms' => 2,
            'typology' => 'T2',
            'requires_accessibility' => false,
            'special_condition_key' => null,
            'priority_order' => 10,
            'deleted_at' => null,
        ])->save();
    }

    private function seedAllocationRules(
        Program $program,
        Contest $contest,
        AffordableRentRegulatoryProfile $profile,
        User $actor,
    ): void {
        $ruleSet = AllocationRuleSet::withTrashed()->firstOrNew([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'name' => 'Preferências T2 — demonstração de candidaturas',
        ]);
        $ruleSet->forceFill([
            'regulatory_profile_id' => $profile->id,
            'description' => 'Configuração demo de preferências '
                .'habitacionais sem fallback para unidades não selecionadas.',
            'status' => AllocationRuleSetStatus::Active,
            'allocation_method' => AllocationMethod::RankingThenLottery,
            'allow_preferences' => true,
            'minimum_preferences' => 1,
            'maximum_preferences' => 3,
            'preferences_required_before_submission' => true,
            'allow_unselected_unit_fallback' => false,
            'preference_selection_starts_at' => $contest->opens_at,
            'preference_selection_ends_at' => $contest->closes_at,
            'allow_lottery' => true,
            'allow_manual_override' => false,
            'requires_acceptance' => true,
            'acceptance_deadline_days' => 10,
            'auto_call_next_on_refusal' => true,
            'auto_call_next_on_expiry' => true,
            'max_refusals_allowed' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
            'deleted_at' => null,
        ])->save();
    }

    private function seedRentRules(
        Program $program,
        Contest $contest,
        AffordableRentRegulatoryProfile $profile,
        User $actor,
        CarbonImmutable $referenceDate,
    ): void {
        $ruleSet = RentRuleSet::withTrashed()->firstOrNew([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'name' => 'Rendas T2 — demonstração de candidaturas',
        ]);
        $ruleSet->forceFill([
            'regulatory_profile_id' => $profile->id,
            'description' => 'Valores fictícios entre 390,00 € e '
                .'410,00 €, sem valor regulamentar ou contratual.',
            'status' => RentRuleSetStatus::Active,
            'calculation_method' => RentCalculationMethod::EffortRate,
            'income_period' => 'monthly',
            'income_basis' => 'declared_income',
            'effort_rate_percentage' => '35.00',
            'minimum_rent' => '390.00',
            'maximum_rent' => '410.00',
            'minimum_effort_rate_percentage' => null,
            'maximum_effort_rate_percentage' => '35.00',
            'deposit_months' => '1.00',
            'minimum_deposit' => '390.00',
            'maximum_deposit' => '410.00',
            'rounding_mode' => 'nearest',
            'rounding_precision' => 2,
            'effective_from' => $contest->opens_at,
            'effective_until' => $contest->closes_at,
            'requires_manual_approval' => true,
            'allow_manual_override' => false,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
            'deleted_at' => null,
        ])->save();

        $manifest = RentLimitTableManifest::query()->updateOrCreate(
            ['rent_rule_set_id' => $ruleSet->id],
            [
                'regulatory_profile_id' => $profile->id,
                'source_document' => 'Tabela fictícia de demonstração '
                    .'para três habitações T2; não constitui fonte oficial.',
                'source_reference' => 'DEMO-ALC-T2-2026-SEM-VALOR-JURIDICO',
                'source_version' => self::PROFILE_VERSION,
                'effective_from' => $contest->opens_at,
                'effective_until' => $contest->closes_at,
                'checksum' => null,
                'row_count' => 0,
                'municipality_coverage' => [
                    MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
                ],
                'typology_coverage' => ['T2'],
                'validation_status' => RentLimitConfigurationStatus::Configured,
                'demo_only' => true,
                'validated_at' => $referenceDate
                    ->subDays(8)
                    ->setTime(8, 0),
                'validated_by' => $actor->id,
            ],
        );

        RentLimitTableRow::query()->updateOrCreate(
            [
                'manifest_id' => $manifest->id,
                'municipality_code' => MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
                'typology' => 'T2',
            ],
            [
                'minimum_rent' => '390.00',
                'maximum_rent' => '410.00',
                'source_row_reference' => 'DEMO-T2-390-410',
            ],
        );

        $manifest->rows()
            ->where(function ($query): void {
                $query
                    ->where(
                        'municipality_code',
                        '!=',
                        MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
                    )
                    ->orWhere('typology', '!=', 'T2');
            })
            ->delete();

        $manifest->refresh()->load('rows');
        $manifest->forceFill([
            'row_count' => $manifest->rows->count(),
            'checksum' => app(RentLimitTableChecksumService::class)
                ->calculate($manifest->rows),
        ])->save();
    }

    private function seedRegulatorySnapshots(
        Program $program,
        Contest $contest,
        AffordableRentRegulatoryProfile $profile,
        User $actor,
    ): void {
        $snapshots = app(RegulatorySnapshotService::class);

        $snapshots->attach(
            $program,
            $profile,
            RegulatoryContext::ProgramPublication,
            $program->starts_at ?? now(),
            $actor,
            self::SNAPSHOT_ORIGIN,
        );
        $snapshots->attach(
            $contest,
            $profile,
            RegulatoryContext::ContestPublication,
            $contest->opens_at ?? now(),
            $actor,
            self::SNAPSHOT_ORIGIN,
        );
    }

    private function assertCompatibleMunicipality(
        mixed $municipalityId,
        Municipality $municipality,
        string $resource,
    ): void {
        if (
            $municipalityId !== null
            && (int) $municipalityId !== (int) $municipality->id
        ) {
            throw new LogicException(
                "O {$resource} demo já pertence a outro Município.",
            );
        }
    }
}
