<?php

namespace App\Services\Contests;

use App\Enums\ContestStatus;
use App\Enums\ProgramStatus;
use App\Enums\RegulatoryContext;
use App\Models\Contest;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Regulatory\AffordableRentLegalRegimeResolver;
use App\Services\Regulatory\RegulatoryPublicationReadinessService;
use App\Services\Regulatory\RegulatorySnapshotService;
use App\Support\AuditEvents;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContestService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly AffordableRentLegalRegimeResolver $regimeResolver,
        private readonly RegulatoryPublicationReadinessService $publicationReadiness,
        private readonly RegulatorySnapshotService $snapshotService,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Contest
    {
        return DB::transaction(function () use ($data, $actor) {
            $deadlines = Arr::pull($data, 'deadlines', []);
            $juryMembers = Arr::pull($data, 'jury_members', []);
            $program = Program::query()
                ->with(['municipality', 'regulatoryProfile.parentProfile'])
                ->findOrFail((int) $data['program_id']);
            $this->assertActorCanManageProgram($actor, $program);
            $profile = $program->regulatoryProfile;

            if ($profile !== null) {
                $this->regimeResolver->assertProfileMatches(
                    $profile,
                    CarbonImmutable::parse((string) $data['opens_at'], 'Europe/Lisbon'),
                    $program->municipality_id,
                );
                $data['regulatory_profile_id'] = $profile->id;
                $data['legal_regime'] = $profile->legal_regime->value;
            }

            $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['title']);
            $data['status'] = ContestStatus::Draft->value;
            $data['created_by'] = $actor->id;
            $data['updated_by'] = $actor->id;

            $contest = Contest::query()->create($data);
            $this->syncDeadlines($contest, $deadlines);
            $this->syncJuryMembers($contest, $juryMembers);

            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $contest,
                module: 'contests',
                action: 'create',
                description: 'Concurso criado.',
                newValues: $contest->only(['program_id', 'code', 'slug', 'title', 'status', 'opens_at', 'closes_at']),
            );

            return $contest->load(['deadlines', 'juryMembers.user']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Contest $contest, array $data, User $actor): Contest
    {
        return DB::transaction(function () use ($contest, $data, $actor) {
            $deadlines = Arr::pull($data, 'deadlines', []);
            $juryMembers = Arr::pull($data, 'jury_members', []);
            $this->assertActorCanManageContest($actor, $contest);
            $program = Program::query()
                ->with(['municipality', 'regulatoryProfile.parentProfile'])
                ->findOrFail((int) $data['program_id']);
            $this->assertActorCanManageProgram($actor, $program);
            $profile = $program->regulatoryProfile;

            if (
                $contest->regulatory_snapshot_id !== null
                && $contest->regulatory_profile_id !== $profile?->id
            ) {
                throw ValidationException::withMessages([
                    'program_id' => 'O perfil regulamentar de um concurso publicado não pode ser alterado.',
                ]);
            }

            if ($profile !== null) {
                $this->regimeResolver->assertProfileMatches(
                    $profile,
                    CarbonImmutable::parse((string) $data['opens_at'], 'Europe/Lisbon'),
                    $program->municipality_id,
                );
                $data['regulatory_profile_id'] = $profile->id;
                $data['legal_regime'] = $profile->legal_regime->value;
            }

            $before = $contest->only(['program_id', 'code', 'slug', 'title', 'status', 'opens_at', 'closes_at']);
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['title'], $contest);
            $data['updated_by'] = $actor->id;

            $contest->update($data);
            $this->syncDeadlines($contest, $deadlines);
            $this->syncJuryMembers($contest, $juryMembers);

            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $contest,
                module: 'contests',
                action: 'update',
                description: 'Concurso atualizado.',
                oldValues: $before,
                newValues: $contest->refresh()->only(['program_id', 'code', 'slug', 'title', 'status', 'opens_at', 'closes_at']),
            );

            return $contest->load(['deadlines', 'juryMembers.user']);
        });
    }

    public function publish(Contest $contest, User $actor): Contest
    {
        return DB::transaction(function () use ($contest, $actor): Contest {
            $locked = Contest::query()
                ->whereKey($contest->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $program = Program::query()
                ->whereKey($locked->program_id)
                ->lockForUpdate()
                ->with('municipality')
                ->firstOrFail();
            $locked->setRelation('program', $program);
            $this->assertActorCanManageContest($actor, $locked);

            if ($locked->status === ContestStatus::Published) {
                if ($locked->regulatory_snapshot_id === null) {
                    throw ValidationException::withMessages([
                        'regulatory' => 'O concurso publicado não possui snapshot regulamentar bloqueado.',
                    ]);
                }

                return $locked;
            }

            if ($program->status !== ProgramStatus::Published) {
                throw ValidationException::withMessages([
                    'contest' => 'O programa associado deve estar publicado antes de publicar o concurso.',
                ]);
            }

            if ($locked->deadlines()->count() === 0) {
                throw ValidationException::withMessages([
                    'contest' => 'Adicione pelo menos um prazo antes de publicar o concurso.',
                ]);
            }

            if ($locked->opens_at === null) {
                throw ValidationException::withMessages([
                    'contest' => 'Defina a data de abertura antes de publicar o concurso.',
                ]);
            }

            $referenceDate = CarbonImmutable::instance($locked->opens_at);
            $profile = $this->publicationReadiness->assertContestReady($locked, $referenceDate);
            $before = $locked->only(['status', 'published_at']);
            $this->snapshotService->attach(
                $locked,
                $profile,
                RegulatoryContext::ContestPublication,
                $referenceDate,
                $actor,
                'contest_publication',
            );
            $locked->forceFill([
                'status' => ContestStatus::Published->value,
                'published_at' => now(),
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(
                event: AuditEvents::PUBLISH,
                auditable: $locked,
                module: 'contests',
                action: 'publish',
                description: 'Concurso publicado no portal público.',
                oldValues: $before,
                newValues: $locked->refresh()->only(['status', 'published_at', 'legal_regime', 'regulatory_snapshot_id']),
            );

            return $locked->refresh();
        });
    }

    public function delete(Contest $contest): void
    {
        if ($contest->status === ContestStatus::Published) {
            throw ValidationException::withMessages([
                'contest' => 'Não é possível eliminar um concurso publicado.',
            ]);
        }

        $this->auditLogger->record(
            event: AuditEvents::DELETE,
            auditable: $contest,
            module: 'contests',
            action: 'delete',
            description: 'Concurso eliminado.',
            oldValues: $contest->only(['program_id', 'code', 'slug', 'title', 'status']),
        );

        $contest->delete();
    }

    /**
     * @param  list<array<string, mixed>>  $deadlines
     */
    private function syncDeadlines(Contest $contest, array $deadlines): void
    {
        $contest->deadlines()->delete();

        collect($deadlines)
            ->filter(fn (array $deadline) => filled($deadline['label'] ?? null) && filled($deadline['ends_at'] ?? null))
            ->values()
            ->each(fn (array $deadline, int $index) => $contest->deadlines()->create([
                'type' => $deadline['type'],
                'label' => $deadline['label'],
                'starts_at' => $deadline['starts_at'] ?? null,
                'ends_at' => $deadline['ends_at'],
                'description' => $deadline['description'] ?? null,
                'sort_order' => $index,
            ]));
    }

    /**
     * @param  list<array<string, mixed>>  $juryMembers
     */
    private function syncJuryMembers(Contest $contest, array $juryMembers): void
    {
        $contest->juryMembers()->delete();

        collect($juryMembers)
            ->filter(fn (array $member) => filled($member['user_id'] ?? null))
            ->each(fn (array $member) => $contest->juryMembers()->create([
                'user_id' => $member['user_id'],
                'role_in_jury' => $member['role_in_jury'],
                'appointed_at' => now(),
            ]));
    }

    private function uniqueSlug(?string $slug, string $title, ?Contest $ignore = null): string
    {
        $base = Str::slug($slug ?: $title) ?: 'concurso';
        $candidate = $base;
        $suffix = 2;

        $query = Contest::withTrashed()->where('slug', $candidate);
        if ($ignore !== null) {
            $query->where('id', '!=', $ignore->getKey());
        }

        while ($query->exists()) {
            $candidate = $base.'-'.$suffix++;
            $query = Contest::withTrashed()->where('slug', $candidate);
            if ($ignore !== null) {
                $query->where('id', '!=', $ignore->getKey());
            }
        }

        return $candidate;
    }

    private function assertActorCanManageProgram(User $actor, Program $program): void
    {
        if (! $this->municipalScope->ownsProgram($actor, $program)) {
            throw ValidationException::withMessages([
                'program_id' => 'Não tem autorização para configurar concursos deste Município.',
            ]);
        }
    }

    private function assertActorCanManageContest(User $actor, Contest $contest): void
    {
        if (! $this->municipalScope->ownsContest($actor, $contest)) {
            throw ValidationException::withMessages([
                'contest' => 'Não tem autorização para alterar este concurso.',
            ]);
        }
    }
}
