<?php

namespace Database\Factories;

use App\Enums\DocumentAccessAction;
use App\Models\DocumentAccessLog;
use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAccessLog>
 */
class DocumentAccessLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_submission_id' => DocumentSubmission::factory(),
            'document_version_id' => null,
            'user_id' => User::factory(),
            'action' => DocumentAccessAction::View->value,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Feature test',
            'url' => 'https://example.test/documentos',
        ];
    }
}
