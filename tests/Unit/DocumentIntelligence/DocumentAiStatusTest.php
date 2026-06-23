<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Enums\DocumentAiStatus;
use PHPUnit\Framework\TestCase;

class DocumentAiStatusTest extends TestCase
{
    public function test_document_ai_status_values_and_options_are_stable(): void
    {
        $this->assertSame('pending', DocumentAiStatus::Pending->value);
        $this->assertSame('processing', DocumentAiStatus::Processing->value);
        $this->assertSame('completed', DocumentAiStatus::Completed->value);
        $this->assertSame('failed', DocumentAiStatus::Failed->value);
        $this->assertSame('manual_review', DocumentAiStatus::ManualReview->value);

        $this->assertSame([
            'pending' => 'Pendente',
            'processing' => 'Em processamento',
            'completed' => 'Concluída',
            'failed' => 'Falhada',
            'manual_review' => 'Revisão manual',
        ], DocumentAiStatus::options());
    }
}
