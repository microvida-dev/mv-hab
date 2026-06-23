<?php

namespace Database\Factories;

use App\Enums\AdministrativeNoteVisibility;
use App\Models\AdministrativeProcess;
use App\Models\AdministrativeProcessNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdministrativeProcessNote>
 */
class AdministrativeProcessNoteFactory extends Factory
{
    public function definition(): array
    {
        $process = AdministrativeProcess::factory()->create();

        return [
            'administrative_process_id' => $process->id,
            'application_id' => $process->application_id,
            'user_id' => User::factory(),
            'visibility' => AdministrativeNoteVisibility::Internal->value,
            'note_type' => 'general',
            'body' => 'Nota interna fictícia para teste.',
        ];
    }
}
