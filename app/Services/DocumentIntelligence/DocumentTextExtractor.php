<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\OcrResult;
use App\Enums\DocumentAiOcrStatus;
use App\Models\DocumentAiAnalysis;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class DocumentTextExtractor
{
    public function __construct(
        private readonly DocumentOcrExtractor $ocrExtractor,
        private readonly DocumentImagePreprocessor $imagePreprocessor,
    ) {}

    public function extract(DocumentAiAnalysis $analysis): OcrResult
    {
        $started = hrtime(true);
        $path = $this->localPath($analysis);

        if ($path === null || ! is_file($path)) {
            return new OcrResult(DocumentAiOcrStatus::Failed, false, 'source_missing', null, 0.0, null, $this->elapsedMs($started), ['source_missing'], [], 'source_missing');
        }

        if (! (bool) config('document-ai.ocr.enabled', true)) {
            return new OcrResult(DocumentAiOcrStatus::Skipped, false, 'ocr_disabled', null, 0.0, null, $this->elapsedMs($started), ['ocr_disabled'], [], 'ocr_disabled');
        }

        if ((bool) config('document-ai.ocr.fallback_to_source_text', false)) {
            $text = $this->normalizeText((string) file_get_contents($path));

            return new OcrResult(
                status: $text !== '' ? DocumentAiOcrStatus::Completed : DocumentAiOcrStatus::Unavailable,
                available: $text !== '',
                method: 'source_text_fallback',
                text: $text !== '' ? $text : null,
                qualityScore: $this->qualityScore($text),
                pagesCount: 1,
                durationMs: $this->elapsedMs($started),
                signals: ['source_text_fallback'],
                metadata: ['mime' => $analysis->source_mime],
                failureCode: $text === '' ? 'source_text_empty' : null,
            );
        }

        if ($analysis->source_mime === 'application/pdf') {
            $direct = $this->extractSearchablePdf($path, $started);

            if ($direct->available) {
                return $direct;
            }
        }

        if (in_array($analysis->source_mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/heic', 'image/heif', 'image/webp'], true)) {
            return $this->extractViaOcr($path, $analysis->source_mime, $started);
        }

        return new OcrResult(DocumentAiOcrStatus::Unavailable, false, 'unsupported_mime', null, 0.0, null, $this->elapsedMs($started), ['unsupported_mime'], ['mime' => $analysis->source_mime], 'unsupported_mime');
    }

    private function extractSearchablePdf(string $path, int $started): OcrResult
    {
        $binary = $this->configString('document-ai.pdf.pdftotext_binary', 'pdftotext');

        if (! $this->binaryAvailable($binary)) {
            return new OcrResult(DocumentAiOcrStatus::Unavailable, false, 'pdf_text_direct', null, 0.0, null, $this->elapsedMs($started), ['pdftotext_unavailable'], ['engine' => 'poppler'], 'pdftotext_unavailable');
        }

        try {
            $process = new Process([$binary, '-layout', $path, '-']);
            $process->setTimeout((int) config('document-ai.ocr.timeout', 120));
            $process->run();

            if (! $process->isSuccessful()) {
                return new OcrResult(DocumentAiOcrStatus::Failed, false, 'pdf_text_direct', null, 0.0, null, $this->elapsedMs($started), ['pdftotext_failed'], ['engine' => 'poppler'], 'pdftotext_failed');
            }

            $text = $this->normalizeText($process->getOutput());

            return new OcrResult(
                status: $text !== '' ? DocumentAiOcrStatus::Completed : DocumentAiOcrStatus::Unavailable,
                available: $text !== '',
                method: 'pdf_text_direct',
                text: $text !== '' ? $text : null,
                qualityScore: $this->qualityScore($text),
                pagesCount: null,
                durationMs: $this->elapsedMs($started),
                signals: ['pdf_searchable_text'],
                metadata: ['engine' => 'poppler'],
                failureCode: $text === '' ? 'pdf_text_empty' : null,
            );
        } catch (Throwable) {
            return new OcrResult(DocumentAiOcrStatus::Failed, false, 'pdf_text_direct', null, 0.0, null, $this->elapsedMs($started), ['pdftotext_exception'], ['engine' => 'poppler'], 'pdftotext_exception');
        }
    }

    private function extractViaOcr(string $path, ?string $mime, int $started): OcrResult
    {
        $prepared = $this->imagePreprocessor->prepare($path, $mime);

        if ($prepared['failure_code'] !== null) {
            return new OcrResult(DocumentAiOcrStatus::Unavailable, false, $prepared['method'], null, 0.0, $prepared['pages_count'], $this->elapsedMs($started), [$prepared['failure_code']], ['method' => $prepared['method']], $prepared['failure_code']);
        }

        $texts = [];
        $signals = [$prepared['method']];

        foreach ($prepared['paths'] as $preparedPath) {
            $result = $this->ocrExtractor->extractImage($preparedPath, $prepared['method']);

            if ($result->failureCode !== null) {
                $this->imagePreprocessor->cleanup($prepared['cleanup_paths']);

                return $result;
            }

            if ($result->text !== null) {
                $texts[] = $result->text;
            }
            $signals = [...$signals, ...$result->signals];
        }

        $this->imagePreprocessor->cleanup($prepared['cleanup_paths']);

        $text = $this->normalizeText(implode("\n", $texts));

        return new OcrResult(
            status: $text !== '' ? DocumentAiOcrStatus::Completed : DocumentAiOcrStatus::Unavailable,
            available: $text !== '',
            method: $prepared['method'],
            text: $text !== '' ? $text : null,
            qualityScore: $this->qualityScore($text),
            pagesCount: $prepared['pages_count'],
            durationMs: $this->elapsedMs($started),
            signals: array_values(array_unique($signals)),
            metadata: ['mime' => $mime],
            failureCode: $text === '' ? 'ocr_text_empty' : null,
        );
    }

    private function localPath(DocumentAiAnalysis $analysis): ?string
    {
        if ($analysis->source_disk === null || $analysis->source_path === null) {
            return null;
        }

        try {
            return Storage::disk($analysis->source_disk)->path($analysis->source_path);
        } catch (Throwable) {
            return null;
        }
    }

    private function binaryAvailable(string $binary): bool
    {
        try {
            $process = new Process([$binary, '--version']);
            $process->setTimeout(5);
            $process->run();

            return $process->isSuccessful();
        } catch (Throwable) {
            return false;
        }
    }

    private function normalizeText(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function qualityScore(string $text): float
    {
        $length = mb_strlen(trim($text));

        if ($length === 0) {
            return 0.0;
        }

        return round(min(1.0, max(0.30, $length / 1000)), 2);
    }

    private function elapsedMs(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }

    private function configString(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
