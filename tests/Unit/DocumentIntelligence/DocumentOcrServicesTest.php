<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Enums\DocumentAiOcrStatus;
use App\Models\DocumentAiAnalysis;
use App\Services\DocumentIntelligence\DocumentImagePreprocessor;
use App\Services\DocumentIntelligence\DocumentOcrExtractor;
use App\Services\DocumentIntelligence\DocumentTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentOcrServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_ocr_extractor_supports_source_text_fallback_for_tests_and_local_fixtures(): void
    {
        config(['document-ai.ocr.fallback_to_source_text' => true]);
        $path = sys_get_temp_dir().'/mvhab-ocr-fallback-test.txt';
        file_put_contents($path, 'Declaracao de rendimentos IRS Modelo 3');

        $result = app(DocumentOcrExtractor::class)->extractImage($path);

        $this->assertSame(DocumentAiOcrStatus::Completed, $result->status);
        $this->assertTrue($result->available);
        $this->assertStringContainsString('IRS', (string) $result->text);

        @unlink($path);
    }

    public function test_text_extractor_reads_private_storage_source_without_exposing_public_path(): void
    {
        Storage::fake('local');
        config(['document-ai.ocr.fallback_to_source_text' => true]);
        Storage::disk('local')->put('documents/ai/teste.pdf', 'Comprovativo de IBAN PT50000000000000000000000');
        $analysis = DocumentAiAnalysis::factory()->create([
            'source_disk' => 'local',
            'source_path' => 'documents/ai/teste.pdf',
            'source_mime' => 'application/pdf',
        ]);

        $result = app(DocumentTextExtractor::class)->extract($analysis);

        $this->assertTrue($result->available);
        $this->assertSame('source_text_fallback', $result->method);
        $this->assertStringContainsString('IBAN', (string) $result->text);
    }

    public function test_image_preprocessor_fails_controlled_when_pdf_tool_is_unavailable(): void
    {
        config(['document-ai.pdf.pdftoppm_binary' => 'missing-pdftoppm-s28']);

        $prepared = app(DocumentImagePreprocessor::class)->prepare(__FILE__, 'application/pdf');

        $this->assertSame('pdftoppm_unavailable', $prepared['failure_code']);
        $this->assertSame([], $prepared['paths']);
    }
}
