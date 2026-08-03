<?php

namespace App\Services\Programs;

use App\Enums\ProgramStatus;
use App\Enums\RegulatoryContext;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Services\Regulatory\AffordableRentLegalRegimeResolver;
use App\Services\Regulatory\MunicipalRegulatoryOverlayService;
use App\Services\Regulatory\RegulatoryPublicationReadinessService;
use App\Services\Regulatory\RegulatorySnapshotService;
use App\Support\AuditEvents;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProgramService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly AffordableRentLegalRegimeResolver $regimeResolver,
        private readonly MunicipalRegulatoryOverlayService $overlayService,
        private readonly RegulatoryPublicationReadinessService $publicationReadiness,
        private readonly RegulatorySnapshotService $snapshotService,
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Program
    {
        return DB::transaction(function () use ($data, $actor) {
            $rules = Arr::pull($data, 'rules', []);
            $this->assertActorCanManageMunicipality($actor, (int) $data['municipality_id']);
            $profile = AffordableRentRegulatoryProfile::query()
                ->with('parentProfile')
                ->findOrFail((int) $data['regulatory_profile_id']);
            $referenceDate = CarbonImmutable::parse((string) $data['starts_at'], 'Europe/Lisbon');
            $this->regimeResolver->assertProfileMatches(
                $profile,
                $referenceDate,
                (int) $data['municipality_id'],
            );
            $this->overlayService->assertValid($profile);
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['name']);
            $data['legal_regime'] = $profile->legal_regime->value;
            $data['status'] = ProgramStatus::Draft->value;
            $data['created_by'] = $actor->id;
            $data['updated_by'] = $actor->id;

            $program = Program::query()->create($data);
            $this->syncRules($program, $rules);

            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $program,
                module: 'programs',
                action: 'create',
                description: 'Programa criado.',
                newValues: $program->only(['municipality_id', 'name', 'slug', 'status', 'starts_at', 'ends_at']),
            );

            return $program->load('rules');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Program $program, array $data, User $actor): Program
    {
        return DB::transaction(function () use ($program, $data, $actor) {
            $rules = Arr::pull($data, 'rules', []);
            $this->assertActorCanManageMunicipality($actor, $program->municipality_id);
            $this->assertActorCanManageMunicipality($actor, (int) $data['municipality_id']);
            $profile = AffordableRentRegulatoryProfile::query()
                ->with('parentProfile')
                ->findOrFail((int) $data['regulatory_profile_id']);
            $referenceDate = CarbonImmutable::parse((string) $data['starts_at'], 'Europe/Lisbon');

            if (
                $program->regulatory_snapshot_id !== null
                && $program->regulatory_profile_id !== $profile->id
            ) {
                throw ValidationException::withMessages([
                    'regulatory_profile_id' => 'O perfil regulamentar de um programa publicado não pode ser alterado.',
                ]);
            }

            $this->regimeResolver->assertProfileMatches(
                $profile,
                $referenceDate,
                (int) $data['municipality_id'],
            );
            $this->overlayService->assertValid($profile);
            $before = $program->only(['municipality_id', 'name', 'slug', 'status', 'starts_at', 'ends_at']);
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['name'], $program);
            $data['legal_regime'] = $profile->legal_regime->value;
            $data['updated_by'] = $actor->id;

            $program->update($data);
            $this->syncRules($program, $rules);

            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $program,
                module: 'programs',
                action: 'update',
                description: 'Programa atualizado.',
                oldValues: $before,
                newValues: $program->refresh()->only(['municipality_id', 'name', 'slug', 'status', 'starts_at', 'ends_at']),
            );

            return $program->load('rules');
        });
    }

    public function publish(Program $program, User $actor): Program
    {
        return DB::transaction(function () use ($program, $actor): Program {
            $locked = Program::query()
                ->whereKey($program->getKey())
                ->lockForUpdate()
                ->with(['municipality', 'regulatoryProfile.parentProfile'])
                ->firstOrFail();
            $this->assertActorCanManageMunicipality($actor, $locked->municipality_id);

            if ($locked->status === ProgramStatus::Published) {
                if ($locked->regulatory_snapshot_id === null) {
                    throw ValidationException::withMessages([
                        'regulatory' => 'O programa publicado não possui snapshot regulamentar bloqueado.',
                    ]);
                }

                return $locked;
            }

            if ($locked->rules()->count() === 0) {
                throw ValidationException::withMessages([
                    'program' => 'Adicione pelo menos uma regra pública antes de publicar o programa.',
                ]);
            }

            if ($locked->starts_at === null) {
                throw ValidationException::withMessages([
                    'program' => 'Defina a data de início do programa antes da publicação.',
                ]);
            }

            $referenceDate = CarbonImmutable::instance($locked->starts_at)->startOfDay();
            $profile = $this->publicationReadiness->assertProgramReady($locked, $referenceDate);
            $before = $locked->only(['status', 'published_at']);
            $this->snapshotService->attach(
                $locked,
                $profile,
                RegulatoryContext::ProgramPublication,
                $referenceDate,
                $actor,
                'program_publication',
            );
            $locked->forceFill([
                'status' => ProgramStatus::Published->value,
                'published_at' => now(),
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(
                event: AuditEvents::PUBLISH,
                auditable: $locked,
                module: 'programs',
                action: 'publish',
                description: 'Programa publicado no portal público.',
                oldValues: $before,
                newValues: $locked->refresh()->only(['status', 'published_at', 'legal_regime', 'regulatory_snapshot_id']),
            );

            return $locked->refresh();
        });
    }

    public function delete(Program $program): void
    {
        if ($program->status === ProgramStatus::Published || $program->contests()->exists()) {
            throw ValidationException::withMessages([
                'program' => 'Não é possível eliminar um programa publicado ou com concursos associados.',
            ]);
        }

        $this->auditLogger->record(
            event: AuditEvents::DELETE,
            auditable: $program,
            module: 'programs',
            action: 'delete',
            description: 'Programa eliminado.',
            oldValues: $program->only(['municipality_id', 'name', 'slug', 'status']),
        );

        $program->delete();
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     */
    private function syncRules(Program $program, array $rules): void
    {
        $program->rules()->delete();

        collect($rules)
            ->filter(fn (array $rule) => filled($rule['title'] ?? null) && filled($rule['description'] ?? null))
            ->values()
            ->each(fn (array $rule, int $index) => $program->rules()->create([
                'title' => $rule['title'],
                'description' => $rule['description'],
                'effective_from' => $rule['effective_from'] ?? null,
                'effective_until' => $rule['effective_until'] ?? null,
                'sort_order' => $index,
            ]));
    }

    private function uniqueSlug(?string $slug, string $name, ?Program $ignore = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'programa';
        $candidate = $base;
        $suffix = 2;

        $query = Program::withTrashed()->where('slug', $candidate);
        if ($ignore !== null) {
            $query->where('id', '!=', $ignore->getKey());
        }

        while ($query->exists()) {
            $candidate = $base.'-'.$suffix++;
            $query = Program::withTrashed()->where('slug', $candidate);
            if ($ignore !== null) {
                $query->where('id', '!=', $ignore->getKey());
            }
        }

        return $candidate;
    }

    private function assertActorCanManageMunicipality(User $actor, int $municipalityId): void
    {
        if ($this->platformScope->hasGlobalScope($actor)) {
            return;
        }

        if ($actor->municipality_id === null || $actor->municipality_id !== $municipalityId) {
            throw ValidationException::withMessages([
                'municipality_id' => 'Não tem autorização para configurar programas deste Município.',
            ]);
        }
    }
}
