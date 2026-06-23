<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeNoteVisibility;
use App\Models\AdministrativeProcess;
use App\Models\AdministrativeProcessNote;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class AdministrativeProcessNoteService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(AdministrativeProcess $process, array $data, User $actor): AdministrativeProcessNote
    {
        $note = new AdministrativeProcessNote([
            'visibility' => $data['visibility'] ?? AdministrativeNoteVisibility::Internal->value,
            'note_type' => $data['note_type'] ?? 'general',
            'body' => $data['body'],
        ]);
        $note->forceFill([
            'administrative_process_id' => $process->id,
            'application_id' => $process->application_id,
            'user_id' => $actor->id,
        ]);
        $note->save();

        $this->auditLogger->record(AuditEvents::CREATE, $note, 'administrative_processes', 'note_create', 'Nota de processo criada.');

        return $note->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AdministrativeProcessNote $note, array $data, User $actor): AdministrativeProcessNote
    {
        $note->fill($data)->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $note, 'administrative_processes', 'note_update', 'Nota de processo atualizada.');

        return $note->refresh();
    }

    public function delete(AdministrativeProcessNote $note, User $actor): void
    {
        $note->delete();
        $this->auditLogger->record(AuditEvents::DELETE, $note, 'administrative_processes', 'note_delete', 'Nota de processo removida.');
    }
}
