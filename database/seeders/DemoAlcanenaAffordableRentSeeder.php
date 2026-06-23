<?php

namespace Database\Seeders;

use App\Enums\AllocationMethod;
use App\Enums\AllocationRuleSetStatus;
use App\Enums\CommunicationChannel;
use App\Enums\ContestDeadlineType;
use App\Enums\ContestHousingUnitStatus;
use App\Enums\ContestStatus;
use App\Enums\ContractClauseStatus;
use App\Enums\ContractTemplateStatus;
use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentCategory;
use App\Enums\EligibilityCriterionCategory;
use App\Enums\EligibilityOperator;
use App\Enums\EligibilityRuleSetStatus;
use App\Enums\HousingLocationPrecision;
use App\Enums\HousingPublicStatus;
use App\Enums\HousingUnitStatus;
use App\Enums\NotificationPriority;
use App\Enums\ProgramStatus;
use App\Enums\PublicVisibilityStatus;
use App\Enums\RentCalculationMethod;
use App\Enums\RentRuleSetStatus;
use App\Enums\RequiredDocumentConditionOperator;
use App\Enums\ScoringCalculationType;
use App\Enums\ScoringOperator;
use App\Enums\ScoringRuleSetStatus;
use App\Enums\TemplateStatus;
use App\Enums\TemplateType;
use App\Enums\TieBreakerDirection;
use App\Models\AdministrativeWorkflowConfig;
use App\Models\AllocationRuleSet;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\ContractClause;
use App\Models\ContractTemplate;
use App\Models\ContractTemplateClause;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\EligibilityRuleSet;
use App\Models\HousingUnit;
use App\Models\Municipality;
use App\Models\NotificationEventRule;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use App\Models\Program;
use App\Models\RentRuleSet;
use App\Models\RequiredDocument;
use App\Models\Role;
use App\Models\ScoringCriterion;
use App\Models\ScoringRuleSet;
use App\Models\TypologyAdequacyRule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoAlcanenaAffordableRentSeeder extends Seeder
{
    public const MUNICIPALITY_CODE = 'ALCANENA';

    public const PROGRAM_SLUG = 'programa-municipal-arrendamento-acessivel-alcanena';

    public const CONTEST_CODE = 'ALC-RAA-01-2026';

    public const RMMG_2026 = 920.00;

    public const MAX_EFFORT_RATE = 35.00;

    public function run(): void
    {
        $requiredRoles = ['administrator', 'municipal_technician', 'jury', 'candidate'];

        if (Role::query()->whereIn('name', $requiredRoles)->count() < count($requiredRoles)) {
            $this->call(SystemAccessSeeder::class);
        }

        DB::transaction(function (): void {
            $administrator = User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'administrator'))
                ->first();

            $municipality = Municipality::query()->updateOrCreate(
                ['code' => self::MUNICIPALITY_CODE],
                [
                    'name' => 'Município de Alcanena',
                    'tax_number' => null,
                    'contact_email' => 'habitacao.alcanena@example.test',
                    'settings' => [
                        'public_portal' => true,
                        'demo_configuration' => true,
                        'legal_validation_required_before_publication' => true,
                    ],
                    'active' => true,
                ],
            );

            $this->seedDemoAccessUsers($municipality);

            $program = Program::withTrashed()->firstOrNew(['slug' => self::PROGRAM_SLUG]);
            $program->forceFill([
                'municipality_id' => $municipality->id,
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
                'name' => 'Programa Municipal de Arrendamento Acessível de Alcanena',
                'summary' => 'Arrendamento municipal acessível para agregados com rendimentos compatíveis e seleção por inscrição e classificação.',
                'description' => 'Programa municipal em regime de renda acessível, aplicável a habitações ou partes de habitação propriedade ou na posse do Município de Alcanena, destinadas ao arrendamento acessível no concelho.',
                'legal_basis' => 'Regulamento Municipal de Arrendamento Acessível de Alcanena — Edital n.º 1820/2024; Decreto-Lei n.º 68/2019; Portaria n.º 175/2019, na redação da Portaria n.º 52/2024.',
                'status' => ProgramStatus::Published,
                'starts_at' => CarbonImmutable::create(2026, 1, 1),
                'ends_at' => null,
                'published_at' => CarbonImmutable::create(2026, 1, 2, 9),
                'deleted_at' => null,
            ])->save();

            $this->seedProgramRules($program);

            $contest = Contest::withTrashed()->firstOrNew(['code' => self::CONTEST_CODE]);
            $contest->forceFill([
                'program_id' => $program->id,
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
                'slug' => 'concurso-01-2026-arrendamento-municipal-acessivel-alcanena',
                'title' => 'Concurso n.º 01/2026 — Arrendamento Municipal Acessível de Alcanena',
                'summary' => 'Concurso de simulação por classificação para testar o ciclo municipal de candidatura e atribuição.',
                'description' => 'Configuração de demonstração baseada no Regulamento Municipal de Arrendamento Acessível de Alcanena. O edital de produção deve confirmar as características das habitações, rendas mínimas e máximas e o rendimento anual máximo aplicável.',
                'application_instructions' => 'É obrigatório concluir previamente o Registo de Adesão. A candidatura pode indicar mais de uma habitação por ordem de preferência. Submissão pela plataforma eletrónica ou, quando necessário, por atendimento municipal. A seleção segue a matriz do Anexo I e os empates remanescentes seguem para sorteio público.',
                'status' => ContestStatus::Published,
                'opens_at' => CarbonImmutable::create(2026, 6, 1, 9),
                'closes_at' => CarbonImmutable::create(2026, 12, 31, 17),
                'published_at' => CarbonImmutable::create(2026, 5, 20, 9),
                'deleted_at' => null,
            ])->save();

            $this->seedContestDeadlines($contest);
            $this->seedJuryMembers($contest, $municipality);
            $this->seedAdministrativeWorkflowConfig($program, $contest);

            $this->seedEligibility($program, $contest, $administrator);
            $this->seedScoring($program, $contest, $administrator);
            $this->seedDocuments($program, $contest);
            $this->seedHousingUnits($program, $contest, $administrator);
            $this->seedAllocationRuleSet($program, $contest, $administrator);
            $this->seedRentConfiguration($program, $contest, $administrator);
            $this->seedContractConfiguration($program, $contest, $administrator);
            $this->seedCommunicationTemplates($municipality, $program, $contest, $administrator);
            $this->seedDocumentTemplates($municipality, $program, $contest, $administrator);
        });
    }

    private function seedDemoAccessUsers(Municipality $municipality): void
    {
        $users = [
            ['Administrador Demo Alcanena', 'admin-demo@exemplo.pt', 'administrator'],
            ['Técnico Demo Alcanena', 'tecnico-demo@exemplo.pt', 'municipal_technician'],
            ['Júri Demo Alcanena', 'juri-demo@exemplo.pt', 'jury'],
            ['Candidato Demo Alcanena', 'candidato-demo@exemplo.pt', 'candidate'],
        ];

        foreach ($users as [$name, $email, $role]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'municipality_id' => $municipality->id,
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'email_verified_at' => CarbonImmutable::create(2026, 1, 1, 0),
                    'status' => 'active',
                    'last_login_at' => null,
                ],
            );

            $user->assignRole($role);
        }
    }

    private function seedProgramRules(Program $program): void
    {
        $rules = [
            ['Tipo e regime', 'Arrendamento Municipal Acessível, em regime de renda acessível.', 5],
            ['Objeto e âmbito', 'Acesso ao arrendamento de habitação a custos acessíveis, compatível com o rendimento dos agregados, em imóveis municipais situados no concelho.', 10],
            ['Duração normal', 'Contrato por 5 anos, com possibilidade de recandidatura uma única vez.', 20],
            ['Residência temporária', 'Estudantes, formandos, formadores, técnicos especializados e pessoal docente ou não docente podem ter contrato com prazo mínimo de 9 meses.', 30],
            ['Taxa de esforço', 'A renda mensal não pode ultrapassar 35% do Rendimento Médio Mensal do agregado familiar.', 40],
            ['Forma de atribuição', 'Concurso mediante candidatura prévia, avaliação por júri e ordenação pela matriz do Anexo I.', 50],
            ['Candidaturas múltiplas', 'O candidato pode indicar mais de uma habitação, desde que cumpra os requisitos de acesso.', 60],
            ['Canal de submissão', 'Plataforma eletrónica e/ou atendimento municipal, conforme o aviso de abertura.', 70],
            ['Publicitação e edital', 'Publicitação por aviso ou edital no site oficial do Município e lugares de estilo. O edital deve identificar as habitações e características, rendas mínimas e máximas e rendimento anual máximo de elegibilidade.', 75],
            ['Proteção de dados', 'O tratamento de dados destina-se à gestão do procedimento e tratamento estatístico, sujeito a consentimento ou alternativa presencial prevista no manual.', 80],
        ];

        foreach ($rules as [$title, $description, $sortOrder]) {
            $program->rules()->updateOrCreate(
                ['title' => $title],
                [
                    'description' => $description,
                    'sort_order' => $sortOrder,
                    'effective_from' => CarbonImmutable::create(2026, 1, 1),
                    'effective_until' => null,
                ],
            );
        }
    }

    private function seedContestDeadlines(Contest $contest): void
    {
        $deadlines = [
            [
                ContestDeadlineType::Applications,
                'Período de candidaturas',
                $contest->opens_at,
                $contest->closes_at,
                'Prazo de submissão de candidaturas pela plataforma eletrónica ou atendimento municipal.',
                10,
            ],
            [
                ContestDeadlineType::Corrections,
                'Aperfeiçoamento documental',
                CarbonImmutable::create(2027, 1, 5, 9),
                CarbonImmutable::create(2027, 1, 15, 17),
                'Prazo demo para resposta a pedidos de aperfeiçoamento instrutório.',
                20,
            ],
            [
                ContestDeadlineType::Complaints,
                'Reclamações à lista provisória',
                CarbonImmutable::create(2027, 2, 2, 9),
                CarbonImmutable::create(2027, 2, 12, 17),
                'Prazo demo para reclamações após publicitação da lista provisória.',
                30,
            ],
            [
                ContestDeadlineType::Hearing,
                'Audiência de interessados',
                CarbonImmutable::create(2027, 2, 16, 9),
                CarbonImmutable::create(2027, 2, 26, 17),
                'Prazo demo para audiência prévia antes da decisão final.',
                40,
            ],
            [
                ContestDeadlineType::Other,
                'Aceitação da atribuição',
                CarbonImmutable::create(2027, 3, 9, 9),
                CarbonImmutable::create(2027, 3, 19, 17),
                'Prazo demo para aceitação da habitação atribuída antes da chamada do candidato seguinte.',
                50,
            ],
        ];

        foreach ($deadlines as [$type, $label, $startsAt, $endsAt, $description, $sortOrder]) {
            $contest->deadlines()->updateOrCreate(
                [
                    'type' => $type->value,
                    'label' => $label,
                ],
                [
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'description' => $description.' Datas fictícias sujeitas a validação no edital.',
                    'sort_order' => $sortOrder,
                ],
            );
        }
    }

    private function seedJuryMembers(Contest $contest, Municipality $municipality): void
    {
        $members = [
            ['Júri Alcanena — Presidente', 'jurado.presidente.alcanena@example.test', 'Presidente do júri'],
            ['Júri Alcanena — Vogal Social', 'jurado.vogal-social.alcanena@example.test', 'Vogal efetivo — área social'],
            ['Júri Alcanena — Vogal Jurídico', 'jurado.vogal-juridico.alcanena@example.test', 'Vogal efetivo — área jurídica'],
        ];

        foreach ($members as [$name, $email, $roleInJury]) {
            $user = User::query()->firstOrNew(['email' => $email]);
            $isNew = ! $user->exists;

            $user->forceFill([
                'municipality_id' => $municipality->id,
                'name' => $name,
                'email_verified_at' => CarbonImmutable::create(2026, 1, 1, 0),
                'status' => 'active',
                'last_login_at' => null,
            ]);

            if ($isNew) {
                $user->password = Hash::make(Str::random(64));
            }

            $user->save();
            $user->assignRole('jury');

            $contest->juryMembers()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'role_in_jury' => $roleInJury,
                    'appointed_at' => CarbonImmutable::create(2026, 5, 20, 9),
                ],
            );
        }
    }

    private function seedAdministrativeWorkflowConfig(Program $program, Contest $contest): void
    {
        $config = AdministrativeWorkflowConfig::withTrashed()->firstOrNew([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'name' => 'Workflow administrativo — Alcanena 01/2026',
        ]);

        $config->forceFill([
            'is_active' => true,
            'default_correction_deadline_days' => 10,
            'allow_deadline_extension' => true,
            'max_deadline_extensions' => 1,
            'auto_mark_overdue' => true,
            'requires_decision_approval' => true,
            'deleted_at' => null,
        ])->save();
    }

    private function seedEligibility(Program $program, Contest $contest, ?User $administrator): void
    {
        $ruleSet = EligibilityRuleSet::withTrashed()->firstOrNew([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'name' => 'Elegibilidade — Concurso Alcanena 01/2026',
        ]);
        $ruleSet->forceFill([
            'description' => 'Requisitos dos artigos 8.º e 9.º do Regulamento de Alcanena. Os impedimentos sem campo estruturado exigem validação documental municipal.',
            'status' => EligibilityRuleSetStatus::Active,
            'is_default' => false,
            'starts_at' => $contest->opens_at,
            'ends_at' => $contest->closes_at,
            'created_by' => $administrator?->id,
            'updated_by' => $administrator?->id,
            'deleted_at' => null,
        ])->save();

        $criteria = [
            $this->eligibilityCriterion('registration_is_registered', 'Registo de Adesão finalizado', EligibilityCriterionCategory::Identity, EligibilityOperator::IsTrue, 10, 'Artigo 12.º, n.º 1.'),
            $this->eligibilityCriterion('candidate_is_adult', 'Idade mínima de 18 anos', EligibilityCriterionCategory::Identity, EligibilityOperator::IsTrue, 20, 'Artigo 8.º, alínea a).'),
            $this->eligibilityCriterion('all_household_members_have_valid_residency', 'Nacionalidade portuguesa ou título de residência válido', EligibilityCriterionCategory::Residence, EligibilityOperator::IsTrue, 30, 'Artigo 8.º, alínea b).'),
            $this->eligibilityCriterion('has_household', 'Agregado familiar preenchido', EligibilityCriterionCategory::Household, EligibilityOperator::IsTrue, 40, 'Dados necessários ao cálculo das condições de acesso.'),
            $this->eligibilityCriterion('has_applicant_member', 'Requerente identificado no agregado', EligibilityCriterionCategory::Household, EligibilityOperator::IsTrue, 50, 'Identificação do candidato principal.'),
            $this->eligibilityCriterion('has_income_information', 'Informação de rendimentos completa', EligibilityCriterionCategory::Income, EligibilityOperator::IsTrue, 60, 'Artigos 8.º e 12.º.'),
            $this->eligibilityCriterion('annual_income_within_alcanena_limit', 'Rendimento anual dentro do limite legal', EligibilityCriterionCategory::Income, EligibilityOperator::IsTrue, 70, 'Portaria n.º 175/2019, quadro I, na redação da Portaria n.º 52/2024.', expected: [
                'base_one_person' => 38632,
                'second_person_increment' => 10000,
                'additional_person_increment' => 5000,
                'currency' => 'EUR',
            ]),
            $this->eligibilityCriterion('all_non_dependent_adults_meet_rmmg', 'Rendimento mínimo dos adultos não dependentes', EligibilityCriterionCategory::Income, EligibilityOperator::IsTrue, 80, 'Artigo 8.º, alínea d). RMMG continental de 2026: 920 EUR.', expected: [
                'monthly_minimum' => self::RMMG_2026,
                'currency' => 'EUR',
                'reference_year' => 2026,
            ]),
            $this->eligibilityCriterion('has_current_housing_situation', 'Situação habitacional preenchida', EligibilityCriterionCategory::Housing, EligibilityOperator::IsTrue, 90, 'Informação necessária à instrução da candidatura.'),
            $this->eligibilityCriterion('has_required_documents_submitted', 'Documentos obrigatórios submetidos', EligibilityCriterionCategory::Documents, EligibilityOperator::AllRequiredDocumentsSubmitted, 100, 'Artigo 12.º, n.º 3.'),
            $this->eligibilityCriterion('has_required_documents_validated', 'Documentos obrigatórios validados', EligibilityCriterionCategory::Documents, EligibilityOperator::AllRequiredDocumentsValidated, 110, 'Validação municipal anterior à decisão.', active: true),
            $this->eligibilityCriterion('contest_is_open', 'Concurso publicado e dentro do prazo', EligibilityCriterionCategory::Application, EligibilityOperator::IsTrue, 120, 'A candidatura formal exige concurso aberto.'),
            $this->eligibilityCriterion('no_duplicate_active_application', 'Sem candidatura ativa duplicada', EligibilityCriterionCategory::Application, EligibilityOperator::IsTrue, 130, 'Uma candidatura processual ativa por candidato e concurso.'),
            $this->eligibilityCriterion('typology_is_adequate', 'Composição adequada às tipologias escolhidas', EligibilityCriterionCategory::Typology, EligibilityOperator::IsTrue, 140, 'Artigo 8.º, alínea e), e Portaria n.º 175/2019.'),
            $this->eligibilityCriterion('rent_effort_within_35_percent', 'Taxa de esforço máxima de 35%', EligibilityCriterionCategory::Income, EligibilityOperator::IsTrue, 150, 'Artigos 5.º e 10.º do Regulamento.', expected: [
                'maximum_percentage' => self::MAX_EFFORT_RATE,
            ]),
            $this->manualImpediment('no_declared_property_impediment', 'Sem propriedade, usufruto ou detenção de habitação', 200, 'Artigo 9.º, n.º 1, alínea a).'),
            $this->manualImpediment('no_incompatible_housing_support', 'Sem apoio público habitacional ou habitação pública incompatível', 210, 'Artigo 9.º, n.º 1, alínea b).'),
            $this->manualImpediment('tax_and_social_security_status_regular', 'Situação regularizada na AT e Segurança Social', 220, 'Artigo 9.º, n.º 1, alínea c).'),
            $this->manualImpediment('no_unregulated_municipal_debt', 'Sem dívida municipal não regularizada', 230, 'Artigo 9.º, n.º 1, alínea d).'),
            $this->manualImpediment('no_accumulated_public_housing_support', 'Sem acumulação de apoio público à habitação', 240, 'Artigo 9.º, n.º 1, alínea e).'),
            $this->manualImpediment('no_fraud_or_false_declarations_last_five_years', 'Sem fraude ou falsas declarações nos últimos 5 anos', 250, 'Artigo 9.º, n.º 2, alínea a).'),
            $this->manualImpediment('no_municipal_eviction_or_breach_last_five_years', 'Sem despejo ou incumprimento municipal impeditivo nos últimos 5 anos', 260, 'Artigo 9.º, n.º 2, alíneas b) e c).'),
        ];

        foreach ($criteria as $criterion) {
            $model = $ruleSet->criteria()->withTrashed()->firstOrNew(['code' => $criterion['code']]);
            $model->forceFill([...$criterion, 'deleted_at' => null])->save();
        }
    }

    private function seedScoring(Program $program, Contest $contest, ?User $administrator): void
    {
        $ruleSet = ScoringRuleSet::withTrashed()->firstOrNew([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'name' => 'Matriz Anexo I — Alcanena 01/2026',
        ]);
        $ruleSet->forceFill([
            'description' => 'Matriz de classificação do Anexo I: qualificação 30%, idade média 40%, dependentes 20% e deficiência/multideficiência 10%.',
            'status' => ScoringRuleSetStatus::Active,
            'is_default' => false,
            'starts_at' => $contest->opens_at,
            'ends_at' => $contest->closes_at,
            'created_by' => $administrator?->id,
            'updated_by' => $administrator?->id,
            'deleted_at' => null,
        ])->save();

        $qualification = $this->scoringCriterion(
            $ruleSet,
            'qualification_classification_points',
            'Nível de qualificação dos elementos não dependentes',
            'qualification',
            0.300,
            10,
            'Soma das pontuações individuais: nível 4 ou inferior=1, nível 5=2, nível 6=3, nível 7=4 e nível 8=5.',
        );
        foreach ([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5] as $value => $points) {
            $this->scoringRule($qualification, "Pontuação de qualificação {$value}", ScoringOperator::Equals, ['value' => $value], $points, 0.300, $value * 10);
        }

        $age = $this->scoringCriterion(
            $ruleSet,
            'average_non_dependent_age',
            'Idade média dos elementos não dependentes',
            'age',
            0.400,
            20,
            'Mais de 65 anos=1; 56–65=2; 41–55=3; 31–40=4; 18–30=5.',
        );
        $this->scoringRule($age, 'Mais de 65 anos', ScoringOperator::GreaterThan, ['value' => 65], 1, 0.400, 10);
        $this->scoringRule($age, '56 a 65 anos', ScoringOperator::Between, ['minimum' => 56, 'maximum' => 65], 2, 0.400, 20);
        $this->scoringRule($age, '41 a 55 anos', ScoringOperator::Between, ['minimum' => 41, 'maximum' => 55], 3, 0.400, 30);
        $this->scoringRule($age, '31 a 40 anos', ScoringOperator::Between, ['minimum' => 31, 'maximum' => 40], 4, 0.400, 40);
        $this->scoringRule($age, '18 a 30 anos', ScoringOperator::Between, ['minimum' => 18, 'maximum' => 30], 5, 0.400, 50);

        $dependents = $this->scoringCriterion(
            $ruleSet,
            'number_of_dependents',
            'Número de dependentes',
            'dependency',
            0.200,
            30,
            '0 dependentes=1; 1=2; 2=3; 3=4; 4 ou mais=5.',
        );
        foreach ([0 => 1, 1 => 2, 2 => 3, 3 => 4] as $value => $points) {
            $this->scoringRule($dependents, "{$value} dependentes", ScoringOperator::Equals, ['value' => $value], $points, 0.200, ($value + 1) * 10);
        }
        $this->scoringRule($dependents, '4 ou mais dependentes', ScoringOperator::GreaterThanOrEqual, ['value' => 4], 5, 0.200, 50);

        $disability = $this->scoringCriterion(
            $ruleSet,
            'disability_classification_points',
            'Deficiência ou multideficiência',
            'disability',
            0.100,
            40,
            'Sem deficiência=1; deficiência=2; multideficiência=3. Havendo vários elementos, somam-se as pontuações aplicáveis.',
        );
        foreach ([1 => 1, 2 => 2, 3 => 3] as $value => $points) {
            $this->scoringRule($disability, "Pontuação de deficiência {$value}", ScoringOperator::Equals, ['value' => $value], $points, 0.100, $value * 10);
        }

        $tieBreakers = [
            ['average_age_classification_points', 'Pontuação da idade média', 10],
            ['qualification_classification_points', 'Pontuação da qualificação', 20],
            ['dependents_classification_points', 'Pontuação dos dependentes', 30],
            ['disability_classification_points', 'Pontuação da deficiência', 40],
        ];
        foreach ($tieBreakers as [$code, $name, $priority]) {
            $rule = $ruleSet->tieBreakerRules()->withTrashed()->firstOrNew(['code' => $code]);
            $rule->forceFill([
                'name' => $name,
                'description' => 'Desempate pela ordem decrescente de ponderação prevista no artigo 15.º.',
                'target' => $code,
                'direction' => TieBreakerDirection::Desc,
                'priority_order' => $priority,
                'is_active' => true,
                'deleted_at' => null,
            ])->save();
        }
    }

    private function seedDocuments(Program $program, Contest $contest): void
    {
        $definitions = [
            ['alcanena_identificacao_residencia', 'Identificação civil ou autorização de residência', DocumentCategory::Identification, DocumentAppliesTo::HouseholdMember, false],
            ['alcanena_nif', 'Cartão ou comprovativo de NIF', DocumentCategory::Tax, DocumentAppliesTo::HouseholdMember, false],
            ['alcanena_seguranca_social', 'Cartão ou comprovativo de Segurança Social', DocumentCategory::SocialSecurity, DocumentAppliesTo::HouseholdMember, false],
            ['alcanena_domicilio_fiscal', 'Certidão de domicílio fiscal', DocumentCategory::Tax, DocumentAppliesTo::HouseholdMember, false],
            ['alcanena_nota_liquidacao_irs', 'Nota de liquidação de IRS do ano fiscal anterior', DocumentCategory::Income, DocumentAppliesTo::Household, false],
            ['alcanena_rendimentos_dispensa_irs', 'Comprovativos de rendimentos por dispensa de IRS', DocumentCategory::Income, DocumentAppliesTo::HouseholdMember, false],
            ['alcanena_certidao_predial', 'Certidão da AT relativa à propriedade habitacional', DocumentCategory::Housing, DocumentAppliesTo::HouseholdMember, false],
            ['alcanena_situacao_regular_at', 'Certidão de situação regularizada na AT', DocumentCategory::Tax, DocumentAppliesTo::AdhesionRegistration, false],
            ['alcanena_situacao_regular_iss', 'Certidão de situação regularizada no ISS', DocumentCategory::SocialSecurity, DocumentAppliesTo::AdhesionRegistration, false],
            ['alcanena_atestado_incapacidade', 'Atestado médico de incapacidade multiúso', DocumentCategory::Health, DocumentAppliesTo::HouseholdMember, false],
            ['alcanena_declaracao_gravidez', 'Declaração médica de gravidez', DocumentCategory::Health, DocumentAppliesTo::HouseholdMember, false],
        ];

        foreach ($definitions as $index => [$code, $name, $category, $appliesTo, $requiresExpiry]) {
            $type = DocumentType::withTrashed()->firstOrNew(['code' => $code]);
            $type->forceFill([
                'name' => $name,
                'description' => 'Documento da checklist do artigo 12.º do Regulamento Municipal de Alcanena.',
                'category' => $category,
                'applies_to' => $appliesTo,
                'is_active' => true,
                'is_required_by_default' => false,
                'requires_expiry_date' => $requiresExpiry,
                'requires_issue_date' => false,
                'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                'max_file_size_mb' => 10,
                'sort_order' => ($index + 1) * 10,
                'deleted_at' => null,
            ])->save();
        }

        $requirements = [
            ['alcanena_identificacao_residencia', DocumentAppliesTo::HouseholdMember, 'always', RequiredDocumentConditionOperator::Always, null, 'Obrigatório para todos os elementos. Para cidadãos estrangeiros, anexar autorização de residência válida.'],
            ['alcanena_nif', DocumentAppliesTo::HouseholdMember, 'always', RequiredDocumentConditionOperator::Always, null, 'Obrigatório para todos os elementos, quando aplicável.'],
            ['alcanena_seguranca_social', DocumentAppliesTo::HouseholdMember, 'always', RequiredDocumentConditionOperator::Always, null, 'Obrigatório para todos os elementos, quando aplicável.'],
            ['alcanena_domicilio_fiscal', DocumentAppliesTo::HouseholdMember, 'always', RequiredDocumentConditionOperator::Always, null, 'Certidão individual de domicílio fiscal.'],
            ['alcanena_nota_liquidacao_irs', DocumentAppliesTo::Household, 'always', RequiredDocumentConditionOperator::Always, null, 'Nota de liquidação relativa à totalidade do agregado e ao ano fiscal anterior.'],
            ['alcanena_rendimentos_dispensa_irs', DocumentAppliesTo::HouseholdMember, 'household_member.is_exempt_from_irs', RequiredDocumentConditionOperator::IsTrue, null, 'Aplicável a membros dispensados de entregar IRS que aufiram rendimentos.'],
            ['alcanena_certidao_predial', DocumentAppliesTo::HouseholdMember, 'always', RequiredDocumentConditionOperator::Always, null, 'Certidão negativa ou certidão predial que permita validar o impedimento do artigo 9.º.'],
            ['alcanena_situacao_regular_at', DocumentAppliesTo::AdhesionRegistration, 'always', RequiredDocumentConditionOperator::Always, null, 'Certidão de situação tributária regularizada do candidato/agregado.'],
            ['alcanena_situacao_regular_iss', DocumentAppliesTo::AdhesionRegistration, 'always', RequiredDocumentConditionOperator::Always, null, 'Certidão de situação contributiva regularizada do candidato/agregado.'],
            ['alcanena_atestado_incapacidade', DocumentAppliesTo::HouseholdMember, 'household_member.is_disabled', RequiredDocumentConditionOperator::IsTrue, null, 'Aplicável a elementos com deficiência ou multideficiência declarada.'],
            ['alcanena_declaracao_gravidez', DocumentAppliesTo::HouseholdMember, 'household_member.is_pregnant', RequiredDocumentConditionOperator::IsTrue, null, 'Aplicável ao elemento que declare gravidez.'],
        ];

        foreach ($requirements as $index => [$code, $requiredFor, $conditionKey, $operator, $conditionValue, $instructions]) {
            $documentType = DocumentType::query()->where('code', $code)->firstOrFail();
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
                'instructions' => $instructions,
                'sort_order' => ($index + 1) * 10,
                'deleted_at' => null,
            ])->save();
        }
    }

    private function seedHousingUnits(Program $program, Contest $contest, ?User $administrator): void
    {
        $units = [
            [
                'code' => 'ALC-DEMO-T1-01',
                'address' => 'Morada fictícia A — Alcanena Centro',
                'public_reference' => 'ALC-DEMO-T1-CENTRO',
                'public_title' => 'T1 Alcanena Centro',
                'public_slug' => 't1-alcanena-centro-demo',
                'public_summary' => 'Habitação T1 fictícia no centro de Alcanena para demonstração do portal público.',
                'public_description' => "Habitação municipal fictícia destinada apenas à demonstração da plataforma MV HAB.\n\nIndicada para candidato isolado ou agregado pequeno, sujeita a validação de elegibilidade, tipologia e documentação no concurso.",
                'typology' => 'T1',
                'bedrooms' => 1,
                'rent' => 320.00,
                'min_occupants' => 1,
                'max_occupants' => 2,
                'accessible' => true,
                'parish' => 'Alcanena',
                'locality' => 'Alcanena Centro',
                'postal_code' => '2380-000',
                'floor' => 'R/C',
                'gross_area_sqm' => 58.50,
                'usable_area_sqm' => 49.20,
                'energy_rating' => 'B',
                'location' => 'Zona central de Alcanena',
                'latitude' => 39.45950,
                'longitude' => -8.66740,
                'sort_order' => 10,
            ],
            [
                'code' => 'ALC-DEMO-T2-01',
                'address' => 'Morada fictícia B — Alcanena',
                'public_reference' => 'ALC-DEMO-T2-ALCANENA',
                'public_title' => 'T2 Alcanena',
                'public_slug' => 't2-alcanena-demo',
                'public_summary' => 'Habitação T2 fictícia em Alcanena para validação de candidatura familiar.',
                'public_description' => "Fogo de demonstração para agregados familiares de pequena dimensão.\n\nOs valores apresentados são fictícios e devem ser substituídos pelo edital municipal definitivo antes de produção.",
                'typology' => 'T2',
                'bedrooms' => 2,
                'rent' => 390.00,
                'min_occupants' => 2,
                'max_occupants' => 3,
                'accessible' => false,
                'parish' => 'Alcanena',
                'locality' => 'Alcanena',
                'postal_code' => '2380-000',
                'floor' => '1.º',
                'gross_area_sqm' => 76.40,
                'usable_area_sqm' => 65.10,
                'energy_rating' => 'C',
                'location' => 'Freguesia de Alcanena',
                'latitude' => 39.46210,
                'longitude' => -8.67020,
                'sort_order' => 20,
            ],
            [
                'code' => 'ALC-DEMO-T3-01',
                'address' => 'Morada fictícia C — Minde',
                'public_reference' => 'ALC-DEMO-T3-MINDE',
                'public_title' => 'T3 Minde',
                'public_slug' => 't3-minde-demo',
                'public_summary' => 'Habitação T3 fictícia em Minde para agregado familiar alargado.',
                'public_description' => "Fogo de demonstração para validar tipologia, preferência habitacional e ordenação em concurso.\n\nA localização é aproximada e não corresponde a morada real de candidato ou inquilino.",
                'typology' => 'T3',
                'bedrooms' => 3,
                'rent' => 470.00,
                'min_occupants' => 3,
                'max_occupants' => 5,
                'accessible' => false,
                'parish' => 'Minde',
                'locality' => 'Minde',
                'postal_code' => '2395-000',
                'floor' => '2.º',
                'gross_area_sqm' => 98.00,
                'usable_area_sqm' => 84.30,
                'energy_rating' => 'B-',
                'location' => 'Freguesia de Minde',
                'latitude' => 39.51620,
                'longitude' => -8.68850,
                'sort_order' => 30,
            ],
            [
                'code' => 'ALC-DEMO-T2-MONSANTO',
                'address' => 'Morada fictícia D — Monsanto',
                'public_reference' => 'ALC-DEMO-T2-MONSANTO',
                'public_title' => 'T2 Monsanto',
                'public_slug' => 't2-monsanto-demo',
                'public_summary' => 'Habitação T2 fictícia em Monsanto para demonstrar filtros por freguesia e renda.',
                'public_description' => "Fogo de demonstração complementar para o portal público de oferta habitacional.\n\nPermite testar comparação de rendas, freguesias e tipologias sem recorrer a dados reais.",
                'typology' => 'T2',
                'bedrooms' => 2,
                'rent' => 410.00,
                'min_occupants' => 2,
                'max_occupants' => 4,
                'accessible' => false,
                'parish' => 'Monsanto',
                'locality' => 'Monsanto',
                'postal_code' => '2380-000',
                'floor' => 'R/C',
                'gross_area_sqm' => 81.20,
                'usable_area_sqm' => 69.40,
                'energy_rating' => 'C',
                'location' => 'Freguesia de Monsanto',
                'latitude' => 39.45290,
                'longitude' => -8.71220,
                'sort_order' => 40,
            ],
        ];

        foreach ($units as $unit) {
            $housingUnit = HousingUnit::query()->updateOrCreate(
                ['code' => $unit['code']],
                [
                    'municipality_id' => $program->municipality_id,
                    'address' => $unit['address'],
                    'typology' => $unit['typology'],
                    'bedrooms' => $unit['bedrooms'],
                    'monthly_rent' => $unit['rent'],
                    'status' => HousingUnitStatus::Available,
                    'public_reference' => $unit['public_reference'],
                    'public_title' => $unit['public_title'],
                    'public_slug' => $unit['public_slug'],
                    'public_summary' => $unit['public_summary'],
                    'public_description' => $unit['public_description'],
                    'parish' => $unit['parish'],
                    'locality' => $unit['locality'],
                    'postal_code' => $unit['postal_code'],
                    'floor' => $unit['floor'],
                    'gross_area_sqm' => $unit['gross_area_sqm'],
                    'usable_area_sqm' => $unit['usable_area_sqm'],
                    'energy_rating' => $unit['energy_rating'],
                    'public_location_description' => $unit['location'],
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
                    'seo_title' => $unit['public_title'].' — Arrendamento Acessível de Alcanena',
                    'seo_description' => $unit['public_summary'],
                ],
            );

            $this->seedHousingUnitFeatures($housingUnit, $unit);

            $contestUnit = ContestHousingUnit::withTrashed()->firstOrNew([
                'contest_id' => $contest->id,
                'housing_unit_id' => $housingUnit->id,
            ]);
            $contestUnit->forceFill([
                'program_id' => $program->id,
                'status' => ContestHousingUnitStatus::Available,
                'availability_starts_at' => $contest->opens_at,
                'availability_ends_at' => $contest->closes_at,
                'typology' => $unit['typology'],
                'bedrooms' => $unit['bedrooms'],
                'min_occupants' => $unit['min_occupants'],
                'max_occupants' => $unit['max_occupants'],
                'accessible' => $unit['accessible'],
                'reserved_for_special_condition' => null,
                'monthly_rent' => $unit['rent'],
                'estimated_expenses' => 45.00,
                'notes' => 'Habitação inteiramente fictícia para simulação funcional.',
                'internal_notes' => 'Renda e morada sem valor jurídico ou contratual. Validar edital antes de publicação.',
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
                'deleted_at' => null,
            ])->save();

            $ruleName = $unit['code'] === 'ALC-DEMO-T2-MONSANTO'
                ? "Adequação {$unit['public_reference']} — simulação Alcanena"
                : "Adequação {$unit['typology']} — simulação Alcanena";

            $rule = TypologyAdequacyRule::withTrashed()->firstOrNew([
                'contest_id' => $contest->id,
                'name' => $ruleName,
            ]);
            $rule->forceFill([
                'program_id' => $program->id,
                'description' => "Regra de simulação para {$unit['public_title']}; validar limites finais com o edital e a Portaria aplicável.",
                'is_active' => true,
                'min_household_members' => $unit['min_occupants'],
                'max_household_members' => $unit['max_occupants'],
                'min_adults' => null,
                'max_adults' => null,
                'min_children' => null,
                'max_children' => null,
                'min_bedrooms' => $unit['bedrooms'],
                'max_bedrooms' => $unit['bedrooms'],
                'typology' => $unit['typology'],
                'requires_accessibility' => false,
                'special_condition_key' => null,
                'priority_order' => $unit['sort_order'],
                'deleted_at' => null,
            ])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $unit
     */
    private function seedHousingUnitFeatures(HousingUnit $housingUnit, array $unit): void
    {
        $features = [
            ['area_bruta', 'Área bruta', number_format((float) $unit['gross_area_sqm'], 2, ',', ' ').' m²', 10],
            ['area_util', 'Área útil', number_format((float) $unit['usable_area_sqm'], 2, ',', ' ').' m²', 20],
            ['renda_acessivel', 'Renda acessível', number_format((float) $unit['rent'], 2, ',', ' ').' € / mês', 30],
            ['localizacao_publica', 'Localização pública', (string) $unit['location'], 40],
            ['tipologia_adequada', 'Ocupação de referência', $unit['min_occupants'].' a '.$unit['max_occupants'].' pessoas', 50],
        ];

        foreach ($features as [$key, $label, $value, $sortOrder]) {
            $housingUnit->features()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => $label,
                    'value' => $value,
                    'icon' => null,
                    'sort_order' => $sortOrder,
                    'is_public' => true,
                ],
            );
        }
    }

    private function seedAllocationRuleSet(Program $program, Contest $contest, ?User $administrator): void
    {
        $ruleSet = AllocationRuleSet::withTrashed()->firstOrNew([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'name' => 'Atribuição por classificação — Alcanena 01/2026',
        ]);
        $ruleSet->forceFill([
            'description' => 'Atribuição pela classificação do Anexo I, respeitando preferências e recorrendo a sorteio público apenas para empates remanescentes.',
            'status' => AllocationRuleSetStatus::Active,
            'allocation_method' => AllocationMethod::RankingThenLottery,
            'allow_preferences' => true,
            'allow_lottery' => true,
            'allow_manual_override' => false,
            'requires_acceptance' => true,
            'acceptance_deadline_days' => 10,
            'auto_call_next_on_refusal' => true,
            'auto_call_next_on_expiry' => true,
            'max_refusals_allowed' => null,
            'created_by' => $administrator?->id,
            'updated_by' => $administrator?->id,
            'deleted_at' => null,
        ])->save();
    }

    private function seedRentConfiguration(Program $program, Contest $contest, ?User $administrator): void
    {
        $ruleSet = RentRuleSet::withTrashed()->firstOrNew([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'name' => 'Renda acessível e caução — Alcanena 01/2026',
        ]);
        $ruleSet->forceFill([
            'description' => 'Configuração demo: renda limitada a 35% do RMM do agregado, com rendas de edital entre 320 EUR e 470 EUR e caução de uma mensalidade.',
            'status' => RentRuleSetStatus::Active,
            'calculation_method' => RentCalculationMethod::EffortRate,
            'income_period' => 'monthly',
            'income_basis' => 'declared_income',
            'effort_rate_percentage' => self::MAX_EFFORT_RATE,
            'minimum_rent' => 320.00,
            'maximum_rent' => 470.00,
            'minimum_effort_rate_percentage' => null,
            'maximum_effort_rate_percentage' => self::MAX_EFFORT_RATE,
            'deposit_months' => 1.00,
            'minimum_deposit' => 320.00,
            'maximum_deposit' => 470.00,
            'rounding_mode' => 'nearest',
            'rounding_precision' => 2,
            'effective_from' => $contest->opens_at,
            'effective_until' => $contest->closes_at,
            'requires_manual_approval' => true,
            'allow_manual_override' => true,
            'created_by' => $administrator?->id,
            'updated_by' => $administrator?->id,
            'deleted_at' => null,
        ])->save();

        $rules = [
            [
                'Limite de taxa de esforço',
                'A renda mensal não pode ultrapassar 35% do rendimento médio mensal do agregado.',
                'effort_rate_cap',
                'less_than_or_equal',
                null,
                self::MAX_EFFORT_RATE,
                null,
                self::MAX_EFFORT_RATE,
                null,
                null,
                10,
            ],
            [
                'Renda mínima do edital',
                'Valor mínimo demo para as habitações incluídas no concurso.',
                'minimum_rent',
                'greater_than_or_equal',
                320.00,
                null,
                320.00,
                null,
                320.00,
                null,
                20,
            ],
            [
                'Renda máxima do edital',
                'Valor máximo demo para as habitações incluídas no concurso.',
                'maximum_rent',
                'less_than_or_equal',
                null,
                470.00,
                470.00,
                null,
                null,
                470.00,
                30,
            ],
            [
                'Caução de uma mensalidade',
                'Caução demo equivalente a uma renda mensal, sujeita a validação contratual.',
                'deposit',
                'equals',
                null,
                null,
                null,
                100.00,
                320.00,
                470.00,
                40,
            ],
        ];

        foreach ($rules as [$name, $description, $type, $operator, $minimumValue, $maximumValue, $fixedAmount, $percentage, $minimumResult, $maximumResult, $priority]) {
            $rule = $ruleSet->rules()->withTrashed()->firstOrNew(['name' => $name]);
            $rule->forceFill([
                'description' => $description,
                'rule_type' => $type,
                'operator' => $operator,
                'minimum_value' => $minimumValue,
                'maximum_value' => $maximumValue,
                'fixed_amount' => $fixedAmount,
                'percentage' => $percentage,
                'minimum_result' => $minimumResult,
                'maximum_result' => $maximumResult,
                'priority_order' => $priority,
                'is_active' => true,
                'deleted_at' => null,
            ])->save();
        }
    }

    private function seedContractConfiguration(Program $program, Contest $contest, ?User $administrator): void
    {
        $clauses = [
            [
                'ALC-RAA-OBJETO',
                'Objeto e identificação da habitação',
                'O presente contrato tem por objeto o arrendamento municipal acessível da habitação identificada no procedimento {{contest.code}}, nos termos do edital e do Regulamento Municipal de Arrendamento Acessível de Alcanena.',
                'object',
                10,
            ],
            [
                'ALC-RAA-PRAZO',
                'Prazo contratual',
                'O contrato tem a duração normal de 5 anos, com possibilidade de recandidatura uma única vez, salvo regime específico de residência temporária previsto no regulamento.',
                'duration',
                20,
            ],
            [
                'ALC-RAA-RENDA',
                'Renda e taxa de esforço',
                'A renda mensal é fixada nos termos do edital e não pode exceder 35% do Rendimento Médio Mensal do agregado familiar, sem prejuízo de validação municipal antes da celebração.',
                'rent',
                30,
            ],
            [
                'ALC-RAA-CAUCAO',
                'Caução',
                'A caução demo corresponde a uma mensalidade de renda e deve ser confirmada no ato de celebração contratual.',
                'deposit',
                40,
            ],
            [
                'ALC-RAA-RGPD',
                'Proteção de dados e comunicações',
                'Os dados pessoais são tratados exclusivamente para gestão do procedimento habitacional, execução contratual, obrigações legais, auditoria e comunicações oficiais.',
                'privacy',
                50,
            ],
        ];

        $template = ContractTemplate::withTrashed()->firstOrNew([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'name' => 'Minuta contratual demo — Alcanena 01/2026',
        ]);
        $template->forceFill([
            'description' => 'Minuta de demonstração sujeita a validação jurídica municipal antes de qualquer utilização real.',
            'status' => ContractTemplateStatus::Active,
            'version_number' => 1,
            'template_body' => implode("\n\n", [
                'CONTRATO DE ARRENDAMENTO MUNICIPAL ACESSÍVEL',
                'Entre o Município de Alcanena e {{tenant.name}}, relativo à habitação {{housing_unit.code}}, no âmbito do {{contest.title}}.',
                '{{contract.clauses}}',
                'Texto demo sem valor jurídico, criado apenas para testes integrados da plataforma.',
            ]),
            'header_html' => '<strong>Município de Alcanena</strong><br>Arrendamento Municipal Acessível',
            'footer_html' => 'Documento demo gerado pela plataforma MV HAB. Validar juridicamente antes de produção.',
            'effective_from' => $contest->opens_at,
            'effective_until' => null,
            'created_by' => $administrator?->id,
            'updated_by' => $administrator?->id,
            'deleted_at' => null,
        ])->save();

        foreach ($clauses as [$code, $title, $body, $category, $sortOrder]) {
            $clause = ContractClause::withTrashed()->firstOrNew([
                'program_id' => $program->id,
                'contest_id' => $contest->id,
                'code' => $code,
            ]);
            $clause->forceFill([
                'title' => $title,
                'body' => $body,
                'category' => $category,
                'status' => ContractClauseStatus::Active,
                'is_mandatory' => true,
                'sort_order' => $sortOrder,
                'effective_from' => $contest->opens_at,
                'effective_until' => null,
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
                'deleted_at' => null,
            ])->save();

            ContractTemplateClause::query()->updateOrCreate(
                [
                    'contract_template_id' => $template->id,
                    'contract_clause_id' => $clause->id,
                ],
                [
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedCommunicationTemplates(Municipality $municipality, Program $program, Contest $contest, ?User $administrator): void
    {
        $templates = [
            [
                'alcanena_application_submitted',
                'Candidatura submetida',
                'application_submitted',
                'Confirmação de candidatura submetida',
                'A candidatura {{application.number}} ao {{contest.title}} foi recebida. Guarde o comprovativo na sua área reservada.',
                false,
                NotificationPriority::Normal,
            ],
            [
                'alcanena_correction_requested',
                'Pedido de aperfeiçoamento',
                'correction_requested',
                'Pedido de aperfeiçoamento da candidatura',
                'Existe um pedido de aperfeiçoamento pendente para a candidatura {{application.number}}. Consulte os itens e responda dentro do prazo indicado.',
                true,
                NotificationPriority::High,
            ],
            [
                'alcanena_provisional_list_published',
                'Lista provisória publicada',
                'provisional_list_published',
                'Publicação da lista provisória',
                'A lista provisória do {{contest.title}} foi publicada. Consulte a sua área reservada e, se aplicável, apresente reclamação dentro do prazo.',
                false,
                NotificationPriority::Normal,
            ],
            [
                'alcanena_allocation_offer',
                'Proposta de atribuição',
                'allocation_offer_created',
                'Proposta de atribuição de habitação',
                'Foi registada uma proposta de atribuição no âmbito do {{contest.title}}. A resposta deve ser apresentada dentro do prazo definido.',
                true,
                NotificationPriority::Urgent,
            ],
        ];

        foreach ($templates as [$code, $name, $eventCode, $title, $body, $requiresAcknowledgement, $priority]) {
            $template = NotificationTemplate::withTrashed()->firstOrNew([
                'code' => $code,
                'channel' => CommunicationChannel::InApp->value,
            ]);
            $template->forceFill([
                'municipality_id' => $municipality->id,
                'program_id' => $program->id,
                'contest_id' => $contest->id,
                'name' => $name,
                'description' => 'Template demo específico do concurso de Alcanena.',
                'template_type' => TemplateType::InApp,
                'status' => TemplateStatus::Active,
                'language' => 'pt-PT',
                'subject' => $title,
                'title' => $title,
                'body' => $body,
                'html_body' => '<p>'.$body.'</p>',
                'sms_body' => null,
                'requires_acknowledgement' => $requiresAcknowledgement,
                'is_official' => true,
                'is_default' => false,
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
                'deleted_at' => null,
            ])->save();

            $version = NotificationTemplateVersion::query()->firstOrNew([
                'notification_template_id' => $template->id,
                'version_number' => 1,
            ]);
            $version->forceFill([
                'status' => TemplateStatus::Active,
                'subject' => $title,
                'title' => $title,
                'body' => $body,
                'html_body' => '<p>'.$body.'</p>',
                'sms_body' => null,
                'variables_schema' => [
                    'contest.title' => ['type' => 'string', 'required' => true],
                    'application.number' => ['type' => 'string', 'required' => false],
                ],
                'change_summary' => 'Versão demo inicial para testes de comunicação do concurso de Alcanena.',
                'created_by' => $administrator?->id,
                'approved_by' => $administrator?->id,
                'approved_at' => CarbonImmutable::create(2026, 5, 20, 9),
                'activated_at' => CarbonImmutable::create(2026, 5, 20, 9),
                'archived_at' => null,
            ])->save();

            $template->forceFill(['active_version_id' => $version->id])->save();

            $eventRule = NotificationEventRule::withTrashed()->firstOrNew([
                'contest_id' => $contest->id,
                'event_code' => $eventCode,
                'recipient_type' => 'candidate',
                'channel' => CommunicationChannel::InApp->value,
            ]);
            $eventRule->forceFill([
                'municipality_id' => $municipality->id,
                'program_id' => $program->id,
                'name' => $name,
                'description' => 'Regra demo de comunicação automática para o concurso de Alcanena.',
                'is_active' => true,
                'notification_template_id' => $template->id,
                'requires_acknowledgement' => $requiresAcknowledgement,
                'priority' => $priority,
                'send_immediately' => true,
                'delay_minutes' => 0,
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
                'deleted_at' => null,
            ])->save();
        }
    }

    private function seedDocumentTemplates(Municipality $municipality, Program $program, Contest $contest, ?User $administrator): void
    {
        $templates = [
            [
                'alcanena_application_receipt',
                'Comprovativo de candidatura',
                'application_receipt',
                'Comprovativo de submissão de candidatura',
                'Comprovativo demo da candidatura {{application.number}} submetida ao {{contest.title}} em {{application.submitted_at}}.',
            ],
            [
                'alcanena_correction_notice',
                'Notificação de aperfeiçoamento',
                'correction_notice',
                'Notificação para aperfeiçoamento instrutório',
                'Notifica-se {{candidate.name}} para aperfeiçoar a candidatura {{application.number}} no prazo indicado na plataforma.',
            ],
            [
                'alcanena_provisional_list_notice',
                'Aviso de lista provisória',
                'public_list_notice',
                'Aviso de publicação de lista provisória',
                'Aviso demo de publicação da lista provisória do {{contest.title}}, com prazo de reclamação conforme edital.',
            ],
            [
                'alcanena_allocation_notice',
                'Notificação de atribuição',
                'allocation_notice',
                'Notificação de proposta de atribuição',
                'Notificação demo de proposta de atribuição da habitação {{housing_unit.code}} no âmbito do {{contest.title}}.',
            ],
        ];

        foreach ($templates as [$code, $name, $category, $title, $body]) {
            $template = DocumentTemplate::withTrashed()->firstOrNew([
                'code' => $code,
                'contest_id' => $contest->id,
            ]);
            $template->forceFill([
                'municipality_id' => $municipality->id,
                'program_id' => $program->id,
                'name' => $name,
                'description' => 'Modelo documental demo específico do concurso de Alcanena.',
                'category' => $category,
                'status' => TemplateStatus::Active,
                'language' => 'pt-PT',
                'title' => $title,
                'body' => $body."\n\nDocumento demo sem valor jurídico. Validar antes de produção.",
                'html_body' => '<p>'.$body.'</p><p><strong>Documento demo sem valor jurídico.</strong></p>',
                'header' => 'Município de Alcanena — Arrendamento Municipal Acessível',
                'footer' => 'Gerado pela plataforma MV HAB para ambiente de demonstração.',
                'is_official' => true,
                'is_default' => false,
                'requires_approval' => true,
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
                'deleted_at' => null,
            ])->save();

            $version = DocumentTemplateVersion::query()->firstOrNew([
                'document_template_id' => $template->id,
                'version_number' => 1,
            ]);
            $version->forceFill([
                'status' => TemplateStatus::Active,
                'title' => $title,
                'body' => $template->body,
                'html_body' => $template->html_body,
                'header' => $template->header,
                'footer' => $template->footer,
                'variables_schema' => [
                    'candidate.name' => ['type' => 'string', 'required' => false],
                    'contest.title' => ['type' => 'string', 'required' => true],
                    'application.number' => ['type' => 'string', 'required' => false],
                    'housing_unit.code' => ['type' => 'string', 'required' => false],
                ],
                'change_summary' => 'Versão demo inicial para documentos oficiais do concurso de Alcanena.',
                'created_by' => $administrator?->id,
                'approved_by' => $administrator?->id,
                'approved_at' => CarbonImmutable::create(2026, 5, 20, 9),
                'activated_at' => CarbonImmutable::create(2026, 5, 20, 9),
                'archived_at' => null,
            ])->save();

            $template->forceFill(['active_version_id' => $version->id])->save();
        }
    }

    /**
     * @param  array<string, bool|float|int|string|null>|null  $expected
     * @return array<string, EligibilityCriterionCategory|EligibilityOperator|array<string, bool|float|int|string|null>|bool|int|string|null>
     */
    private function eligibilityCriterion(
        string $code,
        string $name,
        EligibilityCriterionCategory $category,
        EligibilityOperator $operator,
        int $sortOrder,
        string $source,
        ?array $expected = null,
        bool $active = true,
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'description' => $source,
            'category' => $category,
            'target' => 'calculated_value',
            'operator' => $operator,
            'expected_value' => $expected,
            'minimum_value' => null,
            'maximum_value' => null,
            'unit' => null,
            'is_mandatory' => true,
            'requires_manual_review' => false,
            'failure_message' => 'O requisito de acesso não se encontra cumprido.',
            'success_message' => 'Requisito de acesso cumprido.',
            'review_message' => 'O requisito necessita de validação pelos serviços municipais.',
            'sort_order' => $sortOrder,
            'is_active' => $active,
        ];
    }

    /**
     * @return array<string, EligibilityCriterionCategory|EligibilityOperator|array<string, bool|float|int|string|null>|bool|int|string|null>
     */
    private function manualImpediment(string $code, string $name, int $sortOrder, string $source): array
    {
        return [
            ...$this->eligibilityCriterion(
                $code,
                $name,
                EligibilityCriterionCategory::LegalImpediments,
                EligibilityOperator::Custom,
                $sortOrder,
                $source,
            ),
            'target' => 'manual',
            'requires_manual_review' => true,
            'failure_message' => 'Foi identificado um impedimento legal.',
            'review_message' => 'Validar documentalmente este impedimento antes da decisão de elegibilidade.',
        ];
    }

    private function scoringCriterion(
        ScoringRuleSet $ruleSet,
        string $code,
        string $name,
        string $category,
        float $weight,
        int $sortOrder,
        string $description,
    ): ScoringCriterion {
        $criterion = $ruleSet->criteria()->withTrashed()->firstOrNew(['code' => $code]);
        $criterion->forceFill([
            'name' => $name,
            'description' => $description,
            'category' => $category,
            'target' => 'calculated_value',
            'calculation_type' => ScoringCalculationType::Proportional,
            'operator' => ScoringOperator::Exists,
            'expected_value' => null,
            'minimum_value' => null,
            'maximum_value' => null,
            'points' => 0,
            'max_points' => null,
            'weight' => $weight,
            'requires_manual_review' => false,
            'is_exclusionary' => false,
            'is_active' => true,
            'sort_order' => $sortOrder,
            'success_message' => 'Pontuação calculada de acordo com a matriz do Anexo I.',
            'failure_message' => 'Não foi possível aplicar a escala configurada.',
            'review_message' => 'Validar os dados usados no cálculo.',
            'deleted_at' => null,
        ])->save();

        return $criterion;
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $value
     */
    private function scoringRule(
        ScoringCriterion $criterion,
        string $label,
        ScoringOperator $operator,
        array $value,
        float $points,
        float $weight,
        int $sortOrder,
    ): void {
        $rule = $criterion->rules()->withTrashed()->firstOrNew(['label' => $label]);
        $rule->forceFill([
            'description' => 'Escala oficial do Anexo I do Regulamento Municipal de Alcanena.',
            'operator' => $operator,
            'value' => $value,
            'minimum_value' => $value['minimum'] ?? null,
            'maximum_value' => $value['maximum'] ?? null,
            'points' => $points,
            'weight' => $weight,
            'sort_order' => $sortOrder,
            'is_active' => true,
            'deleted_at' => null,
        ])->save();
    }
}
