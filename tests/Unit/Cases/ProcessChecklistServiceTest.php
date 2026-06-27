<?php

namespace Tests\Unit\Cases;

use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Services\Cases\ProcessChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessChecklistServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_document_marks_document_checklist_as_warning(): void
    {
        $application = Application::factory()->submitted()->create();
        DocumentSubmission::factory()->create([
            'application_id' => $application->id,
            'status' => 'submitted',
        ]);

        $items = collect(app(ProcessChecklistService::class)->forApplication($application))->keyBy('label');

        $this->assertSame('warning', $items->get('Documentos obrigatórios')['status']);
        $this->assertSame('pending', $items->get('Elegibilidade')['status']);
    }
}
