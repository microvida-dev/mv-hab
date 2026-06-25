<?php

namespace Tests\Feature\Rgpd;

use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Services\Rgpd\DataSubjectRequestWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataSubjectRequestEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_subject_request_tracks_due_date_without_exposing_subject_data(): void
    {
        $subject = User::factory()->create(['email' => 'titular-rgpd@example.test']);
        $request = DataSubjectRequest::factory()->for($subject)->create([
            'description' => 'Pedido sintético de acesso.',
            'due_at' => now()->addDays(10),
        ]);

        $remaining = app(DataSubjectRequestWorkflowService::class)->remainingDays($request);

        $this->assertGreaterThanOrEqual(9, $remaining);
        $this->assertStringNotContainsString($subject->email, json_encode([
            'request_number' => $request->request_number,
            'status' => $request->status->value,
            'due_at' => $request->due_at?->toDateString(),
        ], JSON_THROW_ON_ERROR));
    }
}
