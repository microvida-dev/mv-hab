<?php

namespace App\Services\Programs;

use App\Enums\ProgramStatus;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProgramService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Program
    {
        return DB::transaction(function () use ($data, $actor) {
            $rules = Arr::pull($data, 'rules', []);
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['name']);
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
            $before = $program->only(['municipality_id', 'name', 'slug', 'status', 'starts_at', 'ends_at']);
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['name'], $program);
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
        if ($program->rules()->count() === 0) {
            throw ValidationException::withMessages([
                'program' => 'Adicione pelo menos uma regra pública antes de publicar o programa.',
            ]);
        }

        $before = $program->only(['status', 'published_at']);

        $program->update([
            'status' => ProgramStatus::Published->value,
            'published_at' => now(),
            'updated_by' => $actor->id,
        ]);

        $this->auditLogger->record(
            event: AuditEvents::PUBLISH,
            auditable: $program,
            module: 'programs',
            action: 'publish',
            description: 'Programa publicado no portal público.',
            oldValues: $before,
            newValues: $program->refresh()->only(['status', 'published_at']),
        );

        $program->refresh();

        return $program;
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
}
