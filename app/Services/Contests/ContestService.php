<?php

namespace App\Services\Contests;

use App\Enums\ContestStatus;
use App\Enums\ProgramStatus;
use App\Models\Contest;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContestService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Contest
    {
        return DB::transaction(function () use ($data, $actor) {
            $deadlines = Arr::pull($data, 'deadlines', []);
            $juryMembers = Arr::pull($data, 'jury_members', []);
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
        $contest->loadMissing('program');

        if (! $contest->program instanceof Program || $contest->program->status !== ProgramStatus::Published) {
            throw ValidationException::withMessages([
                'contest' => 'O programa associado deve estar publicado antes de publicar o concurso.',
            ]);
        }

        if ($contest->deadlines()->count() === 0) {
            throw ValidationException::withMessages([
                'contest' => 'Adicione pelo menos um prazo antes de publicar o concurso.',
            ]);
        }

        $before = $contest->only(['status', 'published_at']);

        $contest->update([
            'status' => ContestStatus::Published->value,
            'published_at' => now(),
            'updated_by' => $actor->id,
        ]);

        $this->auditLogger->record(
            event: AuditEvents::PUBLISH,
            auditable: $contest,
            module: 'contests',
            action: 'publish',
            description: 'Concurso publicado no portal público.',
            oldValues: $before,
            newValues: $contest->refresh()->only(['status', 'published_at']),
        );

        $contest->refresh();

        return $contest;
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
}
