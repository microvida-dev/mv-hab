<?php

namespace App\Services\Municipalities;

use App\Data\Municipalities\InitialMunicipalityCatalogPreview;
use App\Data\Municipalities\InitialMunicipalityCatalogResult;
use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\ContestStatus;
use App\Enums\ProgramStatus;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Services\Security\MfaEnforcementService;
use App\Services\Support\CanonicalJsonHasher;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class AlcanenaInitialCatalogService
{
    public const PROFILE = 'alcanena-2026';

    public const MUNICIPALITY_CODE = 'ALCANENA';

    public const PROGRAM_SLUG = 'programa-municipal-arrendamento-acessivel-alcanena';

    public const CONTEST_CODE = 'ALC-RAA-01-2026';

    public const CONTEST_SLUG = 'concurso-01-2026-arrendamento-municipal-acessivel-alcanena';

    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
        private readonly MfaEnforcementService $mfa,
        private readonly CanonicalJsonHasher $hasher,
        private readonly AuditTrailService $audit,
    ) {}

    public function preview(User $actor, string $municipalityCode, string $profile): InitialMunicipalityCatalogPreview
    {
        $municipality = $this->municipality($municipalityCode);
        $this->assertActor($actor, $municipality);
        $this->assertProfile($profile);

        $definition = $this->definition();
        $fingerprint = $this->hasher->hash($definition);
        $program = Program::withTrashed()->where('slug', self::PROGRAM_SLUG)->first();
        $contest = Contest::withTrashed()->where(function ($query): void {
            $query
                ->where('code', self::CONTEST_CODE)
                ->orWhere('slug', self::CONTEST_SLUG);
        })->first();
        $conflicts = [];

        if ($program instanceof Program && ! $this->programMatches($program, $municipality, $definition['program'])) {
            $conflicts[] = 'program_definition_conflict';
        }

        if ($contest instanceof Contest && ! $this->contestMatches($contest, $program, $definition['contest'])) {
            $conflicts[] = 'contest_definition_conflict';
        }

        $settings = is_array($municipality->settings) ? $municipality->settings : [];
        $catalogSettings = $settings['initial_catalog'] ?? null;
        $idempotent = $program instanceof Program
            && $contest instanceof Contest
            && $conflicts === []
            && is_array($catalogSettings)
            && ($catalogSettings['profile'] ?? null) === self::PROFILE
            && ($catalogSettings['fingerprint'] ?? null) === $fingerprint
            && ($catalogSettings['publication_blocked'] ?? null) === true;

        return new InitialMunicipalityCatalogPreview(
            municipalityCode: $municipality->code,
            profile: self::PROFILE,
            fingerprint: $fingerprint,
            programSlug: self::PROGRAM_SLUG,
            contestCode: self::CONTEST_CODE,
            conflicts: $conflicts,
            idempotentReplay: $idempotent,
        );
    }

    public function provision(User $actor, string $municipalityCode, string $profile): InitialMunicipalityCatalogResult
    {
        $preview = $this->preview($actor, $municipalityCode, $profile);

        if ($preview->hasConflicts()) {
            throw new DomainException(
                'O catálogo inicial foi bloqueado: '.implode(', ', $preview->conflicts).'.',
            );
        }

        $definition = $this->definition();

        [$municipality, $program, $contest, $idempotent] = DB::transaction(function () use (
            $actor,
            $municipalityCode,
            $definition,
        ): array {
            $municipality = Municipality::query()
                ->where('code', strtoupper(trim($municipalityCode)))
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertActor($actor, $municipality);

            $program = Program::withTrashed()
                ->where('slug', self::PROGRAM_SLUG)
                ->lockForUpdate()
                ->first();

            if ($program instanceof Program) {
                if (! $this->programMatches($program, $municipality, $definition['program'])) {
                    throw new DomainException('O Programa existente diverge da definição inicial aprovada.');
                }
            } else {
                $program = Program::query()->create([
                    'municipality_id' => $municipality->id,
                    'regulatory_profile_id' => null,
                    'legal_regime' => null,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                    ...$definition['program'],
                ]);
            }

            $contest = Contest::withTrashed()
                ->where(function ($query): void {
                    $query
                        ->where('code', self::CONTEST_CODE)
                        ->orWhere('slug', self::CONTEST_SLUG);
                })
                ->lockForUpdate()
                ->first();

            if ($contest instanceof Contest) {
                if (! $this->contestMatches($contest, $program, $definition['contest'])) {
                    throw new DomainException('O Concurso existente diverge da definição inicial aprovada.');
                }
            } else {
                $contest = Contest::query()->create([
                    'program_id' => $program->id,
                    'regulatory_profile_id' => null,
                    'legal_regime' => null,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                    ...$definition['contest'],
                ]);
            }

            $settings = is_array($municipality->settings) ? $municipality->settings : [];
            $expectedCatalogSettings = [
                'profile' => self::PROFILE,
                'fingerprint' => $this->hasher->hash($definition),
                'status' => 'pending_official_confirmation',
                'publication_blocked' => true,
                'program_id' => $program->id,
                'contest_id' => $contest->id,
            ];
            $settingsAlreadyMatch = ($settings['initial_catalog'] ?? null) === $expectedCatalogSettings;

            if (! $settingsAlreadyMatch) {
                $settings['initial_catalog'] = $expectedCatalogSettings;
                $municipality->forceFill(['settings' => $settings])->save();
            }

            $idempotent = $program->wasRecentlyCreated === false
                && $contest->wasRecentlyCreated === false
                && $settingsAlreadyMatch;

            if (! $idempotent) {
                $this->audit->record(
                    eventCode: 'municipality_initial_catalog_created',
                    auditable: $municipality,
                    category: AuditEventCategory::System,
                    severity: AuditEventSeverity::Notice,
                    description: 'Catálogo municipal inicial criado em estado de rascunho.',
                    metadata: [
                        'municipality_id' => $municipality->id,
                        'profile' => self::PROFILE,
                        'fingerprint' => $expectedCatalogSettings['fingerprint'],
                        'program_id' => $program->id,
                        'program_status' => ProgramStatus::Draft->value,
                        'contest_id' => $contest->id,
                        'contest_status' => ContestStatus::Draft->value,
                        'publication_blocked' => true,
                        'provisional_dates' => true,
                        'entitlements_activated' => 0,
                    ],
                    actor: $actor,
                    useAuthenticatedUser: false,
                );
            }

            return [$municipality, $program, $contest, $idempotent];
        }, 3);

        return new InitialMunicipalityCatalogResult(
            municipalityId: (int) $municipality->id,
            programId: (int) $program->id,
            contestId: (int) $contest->id,
            programStatus: $program->status->value,
            contestStatus: $contest->status->value,
            idempotentReplay: $idempotent,
        );
    }

    private function municipality(string $code): Municipality
    {
        $normalized = strtoupper(trim($code));

        if ($normalized !== self::MUNICIPALITY_CODE) {
            throw new DomainException('O perfil indicado apenas suporta o Município de Alcanena.');
        }

        $municipality = Municipality::query()
            ->where('code', $normalized)
            ->first();

        if (! $municipality instanceof Municipality) {
            throw new DomainException('O Município de Alcanena ainda não foi criado.');
        }

        return $municipality;
    }

    private function assertActor(User $actor, Municipality $municipality): void
    {
        $global = $actor->municipality_id === null
            && $this->platformScope->hasGlobalScope($actor)
            && $actor->hasPermission('municipalities.create');
        $municipal = (int) $actor->municipality_id === (int) $municipality->id
            && ! $actor->hasRole(['candidate', 'auditor'])
            && $actor->hasPermission('programs.create')
            && $actor->hasPermission('contests.create');

        if (($actor->status ?? 'active') !== 'active' || (! $global && ! $municipal)) {
            throw new AuthorizationException('Sem autorização para provisionar o catálogo municipal inicial.');
        }

        if (! $actor->mfa_required || ! $this->mfa->hasConfirmedDevice($actor)) {
            throw new AuthorizationException('A operação exige MFA obrigatório e confirmado.');
        }
    }

    private function assertProfile(string $profile): void
    {
        if (trim($profile) !== self::PROFILE) {
            throw new DomainException('O perfil de catálogo inicial indicado não existe.');
        }
    }

    /**
     * @return array{
     *     program: array<string, mixed>,
     *     contest: array<string, mixed>
     * }
     */
    private function definition(): array
    {
        return [
            'program' => [
                'name' => 'Programa Municipal de Arrendamento Acessível de Alcanena',
                'slug' => self::PROGRAM_SLUG,
                'summary' => 'Programa municipal de arrendamento acessível em configuração inicial.',
                'description' => 'Programa municipal aplicável a habitações ou partes de habitação propriedade ou na posse do Município de Alcanena, destinado a arrendamento acessível no concelho.',
                'legal_basis' => 'Regulamento Municipal de Arrendamento Acessível de Alcanena — Edital n.º 1820/2024.',
                'status' => ProgramStatus::Draft->value,
                'starts_at' => '2026-01-01',
                'ends_at' => null,
                'published_at' => null,
            ],
            'contest' => [
                'code' => self::CONTEST_CODE,
                'slug' => self::CONTEST_SLUG,
                'title' => 'Concurso n.º 01/2026 — Arrendamento Municipal Acessível de Alcanena',
                'summary' => 'Concurso municipal em configuração inicial, sujeito à confirmação do edital e dos parâmetros oficiais.',
                'description' => 'Concurso criado em rascunho a partir da identidade local existente. Datas, habitações, rendas, critérios, documentos e demais parâmetros permanecem bloqueados até validação oficial.',
                'application_instructions' => null,
                'status' => ContestStatus::Draft->value,
                'opens_at' => CarbonImmutable::create(2026, 6, 1, 9, 0, 0, 'Europe/Lisbon'),
                'closes_at' => CarbonImmutable::create(2026, 12, 31, 17, 0, 0, 'Europe/Lisbon'),
                'published_at' => null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function programMatches(
        Program $program,
        Municipality $municipality,
        array $definition,
    ): bool {
        return $program->deleted_at === null
            && (int) $program->municipality_id === (int) $municipality->id
            && $program->regulatory_profile_id === null
            && $program->regulatory_snapshot_id === null
            && $program->legal_regime === null
            && $program->name === $definition['name']
            && $program->slug === $definition['slug']
            && $program->summary === $definition['summary']
            && $program->description === $definition['description']
            && $program->legal_basis === $definition['legal_basis']
            && $program->status === ProgramStatus::Draft
            && $program->starts_at?->toDateString() === $definition['starts_at']
            && $program->ends_at === null
            && $program->published_at === null;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function contestMatches(
        Contest $contest,
        ?Program $program,
        array $definition,
    ): bool {
        return $program instanceof Program
            && $contest->deleted_at === null
            && (int) $contest->program_id === (int) $program->id
            && $contest->regulatory_profile_id === null
            && $contest->regulatory_snapshot_id === null
            && $contest->legal_regime === null
            && $contest->code === $definition['code']
            && $contest->slug === $definition['slug']
            && $contest->title === $definition['title']
            && $contest->summary === $definition['summary']
            && $contest->description === $definition['description']
            && $contest->application_instructions === null
            && $contest->status === ContestStatus::Draft
            && $contest->opens_at?->equalTo($definition['opens_at'])
            && $contest->closes_at?->equalTo($definition['closes_at'])
            && $contest->published_at === null;
    }
}
