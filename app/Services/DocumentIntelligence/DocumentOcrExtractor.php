<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\OcrResult;
use App\Enums\DocumentAiOcrStatus;
use Symfony\Component\Process\Process;
use Throwable;

class DocumentOcrExtractor
{
    public function extractImage(string $path, string $method = 'tesseract_image'): OcrResult
    {
        $started = hrtime(true);

        if ((bool) config('document-ai.ocr.fallback_to_source_text', false)) {
            return $this->fallbackText($path, $method, $started);
        }

        $binary = $this->configString('document-ai.ocr.binary', 'tesseract');

        if (! $this->binaryAvailable($binary)) {
            return new OcrResult(
                status: DocumentAiOcrStatus::Unavailable,
                available: false,
                method: $method,
                text: null,
                qualityScore: 0.0,
                pagesCount: null,
                durationMs: $this->elapsedMs($started),
                signals: ['tesseract_unavailable'],
                metadata: ['engine' => 'tesseract'],
                failureCode: 'tesseract_unavailable',
            );
        }

        try {
            $process = new Process([
                $binary,
                $path,
                'stdout',
                '-l',
                $this->configString('document-ai.ocr.language', 'por+eng'),
            ]);
            $process->setTimeout((int) config('document-ai.ocr.timeout', 120));
            $process->run();

            if (! $process->isSuccessful()) {
                return new OcrResult(DocumentAiOcrStatus::Failed, false, $method, null, 0.0, null, $this->elapsedMs($started), ['ocr_process_failed'], ['engine' => 'tesseract'], 'ocr_process_failed');
            }

            $text = $this->normalizeText($process->getOutput());

            return new OcrResult(DocumentAiOcrStatus::Completed, $text !== '', $method, $text, $this->qualityScore($text), 1, $this->elapsedMs($started), ['tesseract_ocr'], ['engine' => 'tesseract']);
        } catch (Throwable) {
            return new OcrResult(DocumentAiOcrStatus::Failed, false, $method, null, 0.0, null, $this->elapsedMs($started), ['ocr_exception'], ['engine' => 'tesseract'], 'ocr_exception');
        }
    }

    private function fallbackText(string $path, string $method, int $started): OcrResult
    {
        $text = is_file($path) ? $this->normalizeText((string) file_get_contents($path)) : '';

        return new OcrResult(
            status: $text !== '' ? DocumentAiOcrStatus::Completed : DocumentAiOcrStatus::Unavailable,
            available: $text !== '',
            method: $method.'_source_text_fallback',
            text: $text !== '' ? $text : null,
            qualityScore: $this->qualityScore($text),
            pagesCount: 1,
            durationMs: $this->elapsedMs($started),
            signals: ['source_text_fallback'],
            metadata: ['engine' => 'fallback'],
            failureCode: $text !== '' ? null : 'source_text_empty',
        );
    }

    public function binaryAvailable(string $binary): bool
    {
        if ($binary === '') {
            return false;
        }

        if (str_contains($binary, DIRECTORY_SEPARATOR)) {
            return is_file($binary) && is_executable($binary);
        }

        try {
            $process = Process::fromShellCommandline('command -v '.escapeshellarg($binary));
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

        return min(1.0, max(0.35, $length / 800));
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
