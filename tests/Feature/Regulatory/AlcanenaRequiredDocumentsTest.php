<?php

namespace Tests\Feature\Regulatory;

use App\Models\Contest;
use App\Models\RequiredDocument;
use Database\Seeders\DemoAlcanenaAffordableRentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlcanenaRequiredDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_documents_are_active_and_linked_to_the_alcanena_contest(): void
    {
        $this->seed(DemoAlcanenaAffordableRentSeeder::class);

        $contest = Contest::query()->where('code', DemoAlcanenaAffordableRentSeeder::CONTEST_CODE)->firstOrFail();
        $documents = RequiredDocument::query()
            ->where('contest_id', $contest->id)
            ->with('documentType')
            ->get();

        $this->assertCount(11, $documents);
        $this->assertTrue($documents->every(fn (RequiredDocument $document): bool => $document->is_active && $document->is_required));
        $this->assertTrue($documents->every(fn (RequiredDocument $document): bool => str_starts_with((string) $document->documentType?->code, 'alcanena_')));
        $this->assertSame(10, $documents->sortBy('sort_order')->first()->sort_order);
    }
}
