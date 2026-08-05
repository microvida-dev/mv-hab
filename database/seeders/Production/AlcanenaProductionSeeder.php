<?php

namespace Database\Seeders\Production;

use App\Enums\ConsentLegalBasis;
use App\Enums\ContestDeadlineType;
use App\Enums\ContestStatus;
use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentCategory;
use App\Enums\MunicipalityOnboardingStatus;
use App\Enums\ProgramStatus;
use App\Models\ConsentPurpose;
use App\Models\Contest;
use App\Models\DocumentType;
use App\Models\Municipality;
use App\Models\MunicipalityOnboardingRun;
use App\Models\Program;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class AlcanenaProductionSeeder extends Seeder
{
    public const MUNICIPALITY_CODE = 'ALCANENA';

    public const PROGRAM_SLUG = 'programa-municipal-arrendamento-acessivel-alcanena';

    public const CONTEST_CODE = 'ALC-RAA-01-2026';

    public const CONTEST_SLUG = 'concurso-01-2026-arrendamento-municipal-acessivel-alcanena';

    public function run(): void
    {
        DB::transaction(function (): void {
            $municipality = $this->municipality();
            [$platformActor, $municipalAdministrator] = $this->onboardingActors($municipality);

            $this->seedDocumentTypes();
            $this->seedConsentPurposes($platformActor);

            $program = $this->seedProgram($municipality, $municipalAdministrator);
            $this->seedProgramRules($program);

            $contest = $this->seedContest($program, $municipalAdministrator);
            $this->seedApplicationDeadline($contest);
        }, 3);

        $this->command->info('Baseline de produção de Alcanena criada sem publicar programa ou concurso.');
    }

    private function municipality(): Municipality
    {
        $municipality = Municipality::query()
            ->where('code', self::MUNICIPALITY_CODE)
            ->lockForUpdate()
            ->first();

        if (! $municipality instanceof Municipality) {
            throw new DomainException(
                'O Município de Alcanena não existe. Execute primeiro o onboarding municipal.',
            );
        }

        if (! $municipality->active) {
            throw new DomainException('O Município de Alcanena encontra-se inativo.');
        }

        return $municipality;
    }

    /** @return array{0: User, 1: User} */
    private function onboardingActors(Municipality $municipality): array
    {
        $run = MunicipalityOnboardingRun::query()
            ->where('municipality_code', self::MUNICIPALITY_CODE)
            ->where('municipality_id', $municipality->id)
            ->where('status', MunicipalityOnboardingStatus::Completed->value)
            ->whereNotNull('admin_user_id')
            ->latest('id')
            ->first();

        if (! $run instanceof MunicipalityOnboardingRun) {
            throw new DomainException(
                'Não existe onboarding municipal concluído para o Município de Alcanena.',
            );
        }

        $platformActor = User::query()->find($run->actor_id);
        $municipalAdministrator = User::query()->find($run->admin_user_id);

        if (! $platformActor instanceof User
            || $platformActor->municipality_id !== null
            || $platformActor->status !== 'active') {
            throw new DomainException('O ator global do onboarding não é elegível.');
        }

        if (! $municipalAdministrator instanceof User
            || (int) $municipalAdministrator->municipality_id !== (int) $municipality->id
            || $municipalAdministrator->status !== 'active') {
            throw new DomainException('O administrador municipal de Alcanena não é elegível.');
        }

        return [$platformActor, $municipalAdministrator];
    }

    private function seedDocumentTypes(): void
    {
        foreach ($this->documentTypes() as $definition) {
            $documentType = DocumentType::withTrashed()
                ->where('code', $definition['code'])
                ->first();

            if ($documentType instanceof DocumentType) {
                if ($documentType->trashed()) {
                    throw new DomainException(
                        "O tipo documental {$definition['code']} existe, mas encontra-se eliminado.",
                    );
                }

                continue;
            }

            DocumentType::query()->create($definition);
        }
    }

    private function seedConsentPurposes(User $platformActor): void
    {
        foreach ($this->consentPurposes() as $definition) {
            $purpose = ConsentPurpose::withTrashed()
                ->where('code', $definition['code'])
                ->first();

            if ($purpose instanceof ConsentPurpose) {
                if ($purpose->trashed()) {
                    throw new DomainException(
                        "A finalidade RGPD {$definition['code']} existe, mas encontra-se eliminada.",
                    );
                }

                continue;
            }

            ConsentPurpose::query()->create([
                'municipality_id' => null,
                ...$definition,
                'created_by' => $platformActor->id,
                'updated_by' => $platformActor->id,
            ]);
        }
    }

    private function seedProgram(Municipality $municipality, User $actor): Program
    {
        $conflict = Program::withTrashed()
            ->where('slug', self::PROGRAM_SLUG)
            ->first();

        if ($conflict instanceof Program) {
            if ($conflict->trashed()) {
                throw new DomainException('O Programa de Alcanena existe, mas encontra-se eliminado.');
            }

            if ((int) $conflict->municipality_id !== (int) $municipality->id) {
                throw new DomainException('O slug do Programa de Alcanena pertence a outro Município.');
            }

            return $conflict;
        }

        return Program::query()->create([
            'municipality_id' => $municipality->id,
            'regulatory_profile_id' => null,
            'legal_regime' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
            'name' => 'Programa Municipal de Arrendamento Acessível de Alcanena',
            'slug' => self::PROGRAM_SLUG,
            'summary' => 'Programa municipal de arrendamento acessível em configuração inicial.',
            'description' => 'Programa municipal aplicável a habitações ou partes de habitação propriedade ou na posse do Município de Alcanena, destinado a arrendamento acessível no concelho.',
            'legal_basis' => 'Regulamento Municipal de Arrendamento Acessível de Alcanena — Edital n.º 1820/2024.',
            'status' => ProgramStatus::Draft,
            'starts_at' => CarbonImmutable::create(2026, 1, 1, 0, 0, 0, 'Europe/Lisbon'),
            'ends_at' => null,
            'published_at' => null,
        ]);
    }

    private function seedProgramRules(Program $program): void
    {
        foreach ($this->programRules() as $definition) {
            $program->rules()->firstOrCreate(
                ['title' => $definition['title']],
                $definition,
            );
        }
    }

    private function seedContest(Program $program, User $actor): Contest
    {
        $contest = Contest::withTrashed()
            ->where(function ($query): void {
                $query
                    ->where('code', self::CONTEST_CODE)
                    ->orWhere('slug', self::CONTEST_SLUG);
            })
            ->first();

        if ($contest instanceof Contest) {
            if ($contest->trashed()) {
                throw new DomainException('O Concurso de Alcanena existe, mas encontra-se eliminado.');
            }

            if ((int) $contest->program_id !== (int) $program->id) {
                throw new DomainException('O código ou slug do Concurso de Alcanena pertence a outro Programa.');
            }

            return $contest;
        }

        return Contest::query()->create([
            'program_id' => $program->id,
            'regulatory_profile_id' => null,
            'legal_regime' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
            'code' => self::CONTEST_CODE,
            'slug' => self::CONTEST_SLUG,
            'title' => 'Concurso n.º 01/2026 — Arrendamento Municipal Acessível de Alcanena',
            'summary' => 'Concurso municipal em configuração inicial, sujeito à confirmação do edital e dos parâmetros oficiais.',
            'description' => 'Concurso criado em rascunho a partir da identidade local existente. Datas, habitações, rendas, critérios, documentos e demais parâmetros permanecem bloqueados até validação oficial.',
            'application_instructions' => null,
            'status' => ContestStatus::Draft,
            'opens_at' => CarbonImmutable::create(2026, 6, 1, 9, 0, 0, 'Europe/Lisbon'),
            'closes_at' => CarbonImmutable::create(2026, 12, 31, 17, 0, 0, 'Europe/Lisbon'),
            'published_at' => null,
        ]);
    }

    private function seedApplicationDeadline(Contest $contest): void
    {
        $contest->deadlines()->firstOrCreate(
            [
                'type' => ContestDeadlineType::Applications->value,
                'label' => 'Período de candidaturas — provisório',
            ],
            [
                'starts_at' => $contest->opens_at,
                'ends_at' => $contest->closes_at,
                'description' => 'Datas provisórias sujeitas a confirmação no aviso de abertura do concurso.',
                'sort_order' => 10,
            ],
        );
    }

    /**
     * @return list<array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     category: string,
     *     applies_to: string,
     *     is_active: bool,
     *     is_required_by_default: bool,
     *     requires_expiry_date: bool,
     *     requires_issue_date: bool,
     *     allowed_mime_types: list<string>,
     *     max_file_size_mb: int,
     *     sort_order: int
     * }>
     */
    private function documentTypes(): array
    {
        $definitions = [
            ['documento_identificacao', 'Documento de identificação', DocumentCategory::Identification, DocumentAppliesTo::HouseholdMember],
            ['nif', 'Comprovativo de NIF', DocumentCategory::Tax, DocumentAppliesTo::HouseholdMember],
            ['titulo_residencia', 'Título de residência', DocumentCategory::Identification, DocumentAppliesTo::HouseholdMember],
            ['comprovativo_domicilio_fiscal', 'Comprovativo de domicílio fiscal', DocumentCategory::Tax, DocumentAppliesTo::HouseholdMember],
            ['certidao_predial_negativa', 'Certidão predial negativa', DocumentCategory::Housing, DocumentAppliesTo::HouseholdMember],
            ['irs', 'Declaração de IRS', DocumentCategory::Income, DocumentAppliesTo::HouseholdMember],
            ['nota_liquidacao_irs', 'Nota de liquidação de IRS', DocumentCategory::Income, DocumentAppliesTo::HouseholdMember],
            ['recibos_vencimento', 'Recibos de vencimento', DocumentCategory::Income, DocumentAppliesTo::IncomeRecord],
            ['declaracao_seg_social', 'Declaração da Segurança Social', DocumentCategory::SocialSecurity, DocumentAppliesTo::IncomeRecord],
            ['comprovativo_pensao', 'Comprovativo de pensão', DocumentCategory::Income, DocumentAppliesTo::IncomeRecord],
            ['comprovativo_subsidio_desemprego', 'Comprovativo de subsídio de desemprego', DocumentCategory::Income, DocumentAppliesTo::IncomeRecord],
            ['atestado_incapacidade', 'Atestado médico de incapacidade multiuso', DocumentCategory::Health, DocumentAppliesTo::HouseholdMember],
            ['comprovativo_estudante', 'Comprovativo de estudante', DocumentCategory::Education, DocumentAppliesTo::HouseholdMember],
            ['contrato_arrendamento_atual', 'Contrato de arrendamento atual', DocumentCategory::Housing, DocumentAppliesTo::CurrentHousingSituation],
            ['recibo_renda', 'Recibo de renda', DocumentCategory::Housing, DocumentAppliesTo::CurrentHousingSituation],
            ['declaracao_honra', 'Declaração sob compromisso de honra', DocumentCategory::Declaration, DocumentAppliesTo::AdhesionRegistration],
        ];

        $documentTypes = [];

        foreach ($definitions as $index => $definition) {
            [$code, $name, $category, $appliesTo] = $definition;

            $documentTypes[] = [
                'code' => $code,
                'name' => $name,
                'description' => 'Documento utilizável na preparação e instrução de candidatura municipal.',
                'category' => $category->value,
                'applies_to' => $appliesTo->value,
                'is_active' => true,
                'is_required_by_default' => true,
                'requires_expiry_date' => false,
                'requires_issue_date' => false,
                'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                'max_file_size_mb' => 10,
                'sort_order' => ($index + 1) * 10,
            ];
        }

        return $documentTypes;
    }

    /**
     * @return list<array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     legal_basis: string,
     *     is_required: bool,
     *     is_active: bool,
     *     requires_explicit_consent: bool,
     *     retention_period_months: int
     * }>
     */
    private function consentPurposes(): array
    {
        return [
            [
                'code' => 'application_processing',
                'name' => 'Tratamento de candidatura',
                'description' => 'Tratamento necessário para receber, instruir e decidir candidaturas municipais de arrendamento acessível.',
                'legal_basis' => ConsentLegalBasis::PublicInterest->value,
                'is_required' => true,
                'is_active' => true,
                'requires_explicit_consent' => false,
                'retention_period_months' => 120,
            ],
            [
                'code' => 'document_review',
                'name' => 'Validação documental',
                'description' => 'Tratamento dos documentos declarativos e comprovativos submetidos no procedimento municipal.',
                'legal_basis' => ConsentLegalBasis::LegalObligation->value,
                'is_required' => true,
                'is_active' => true,
                'requires_explicit_consent' => false,
                'retention_period_months' => 120,
            ],
            [
                'code' => 'municipal_communications',
                'name' => 'Comunicações processuais',
                'description' => 'Comunicações eletrónicas e administrativas estritamente relacionadas com o procedimento municipal.',
                'legal_basis' => ConsentLegalBasis::PublicInterest->value,
                'is_required' => true,
                'is_active' => true,
                'requires_explicit_consent' => false,
                'retention_period_months' => 60,
            ],
            [
                'code' => 'optional_feedback',
                'name' => 'Contacto para melhoria do serviço',
                'description' => 'Contacto opcional para recolha de opinião sobre a experiência de utilização da plataforma, sem comunicações comerciais.',
                'legal_basis' => ConsentLegalBasis::Consent->value,
                'is_required' => false,
                'is_active' => true,
                'requires_explicit_consent' => true,
                'retention_period_months' => 24,
            ],
        ];
    }

    /**
     * @return list<array{
     *     title: string,
     *     description: string,
     *     sort_order: int,
     *     effective_from: CarbonImmutable,
     *     effective_until: null
     * }>
     */
    private function programRules(): array
    {
        $effectiveFrom = new CarbonImmutable('2026-01-01 00:00:00', 'Europe/Lisbon');

        return [
            [
                'title' => 'Objeto e âmbito',
                'description' => 'Acesso ao arrendamento de habitações ou partes de habitação propriedade ou na posse do Município de Alcanena, em regime de renda acessível.',
                'sort_order' => 10,
                'effective_from' => $effectiveFrom,
                'effective_until' => null,
            ],
            [
                'title' => 'Taxa de esforço',
                'description' => 'A renda mensal deve respeitar a taxa de esforço máxima prevista no Regulamento Municipal e na legislação aplicável.',
                'sort_order' => 20,
                'effective_from' => $effectiveFrom,
                'effective_until' => null,
            ],
            [
                'title' => 'Forma de atribuição',
                'description' => 'A atribuição é realizada através de concurso, mediante candidatura, análise pelo júri e ordenação nos termos regulamentares.',
                'sort_order' => 30,
                'effective_from' => $effectiveFrom,
                'effective_until' => null,
            ],
            [
                'title' => 'Candidaturas a habitações',
                'description' => 'O candidato pode indicar mais de uma habitação, de acordo com as condições e preferências definidas no aviso de abertura.',
                'sort_order' => 40,
                'effective_from' => $effectiveFrom,
                'effective_until' => null,
            ],
            [
                'title' => 'Instrução e validação',
                'description' => 'Os dados declarados e documentos apresentados ficam sujeitos a validação municipal e a pedidos de aperfeiçoamento nos termos aplicáveis.',
                'sort_order' => 50,
                'effective_from' => $effectiveFrom,
                'effective_until' => null,
            ],
            [
                'title' => 'Publicitação do concurso',
                'description' => 'O aviso de abertura deve definir as habitações, características, rendas, prazos, documentos e demais parâmetros concretos do procedimento.',
                'sort_order' => 60,
                'effective_from' => $effectiveFrom,
                'effective_until' => null,
            ],
        ];
    }
}
