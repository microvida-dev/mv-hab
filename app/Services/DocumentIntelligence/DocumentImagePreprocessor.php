<?php

namespace App\Services\DocumentIntelligence;

use Symfony\Component\Process\Process;
use Throwable;

class DocumentImagePreprocessor
{
    /**
     * @return array{paths: list<string>, method: string, pages_count: int|null, cleanup_paths: list<string>, failure_code: string|null}
     */
    public function prepare(string $path, ?string $mime): array
    {
        return match ($mime) {
            'application/pdf' => $this->preparePdf($path),
            'image/heic', 'image/heif' => $this->convertImage($path, 'heic_to_png'),
            default => [
                'paths' => [$path],
                'method' => 'image_direct',
                'pages_count' => 1,
                'cleanup_paths' => [],
                'failure_code' => null,
            ],
        };
    }

    /**
     * @param  list<string>  $paths
     */
    public function cleanup(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * @return array{paths: list<string>, method: string, pages_count: int|null, cleanup_paths: list<string>, failure_code: string|null}
     */
    private function preparePdf(string $path): array
    {
        $binary = $this->configString('document-ai.pdf.pdftoppm_binary', 'pdftoppm');

        if (! $this->binaryAvailable($binary)) {
            return [
                'paths' => [],
                'method' => 'pdf_to_image',
                'pages_count' => null,
                'cleanup_paths' => [],
                'failure_code' => 'pdftoppm_unavailable',
            ];
        }

        $prefix = tempnam(sys_get_temp_dir(), 'mvhab-pdf-');
        if ($prefix === false) {
            return [
                'paths' => [],
                'method' => 'pdf_to_image',
                'pages_count' => null,
                'cleanup_paths' => [],
                'failure_code' => 'tempfile_unavailable',
            ];
        }
        @unlink($prefix);

        try {
            $process = new Process([
                $binary,
                '-png',
                '-r',
                '150',
                '-f',
                '1',
                '-l',
                (string) max(1, (int) config('document-ai.ocr.max_pages', 10)),
                $path,
                $prefix,
            ]);
            $process->setTimeout((int) config('document-ai.ocr.timeout', 120));
            $process->run();

            if (! $process->isSuccessful()) {
                return [
                    'paths' => [],
                    'method' => 'pdf_to_image',
                    'pages_count' => null,
                    'cleanup_paths' => [],
                    'failure_code' => 'pdf_to_image_failed',
                ];
            }

            $paths = glob($prefix.'*.png') ?: [];
            $paths = array_values(array_filter($paths, 'is_file'));

            return [
                'paths' => $paths,
                'method' => 'pdf_to_image',
                'pages_count' => count($paths),
                'cleanup_paths' => $paths,
                'failure_code' => $paths === [] ? 'pdf_to_image_empty' : null,
            ];
        } catch (Throwable) {
            return [
                'paths' => [],
                'method' => 'pdf_to_image',
                'pages_count' => null,
                'cleanup_paths' => [],
                'failure_code' => 'pdf_to_image_exception',
            ];
        }
    }

    /**
     * @return array{paths: list<string>, method: string, pages_count: int|null, cleanup_paths: list<string>, failure_code: string|null}
     */
    private function convertImage(string $path, string $method): array
    {
        $binary = $this->configString('document-ai.image.magick_binary', 'magick');

        if (! $this->binaryAvailable($binary)) {
            return [
                'paths' => [],
                'method' => $method,
                'pages_count' => null,
                'cleanup_paths' => [],
                'failure_code' => 'magick_unavailable',
            ];
        }

        $target = tempnam(sys_get_temp_dir(), 'mvhab-image-');
        if ($target === false) {
            return [
                'paths' => [],
                'method' => $method,
                'pages_count' => null,
                'cleanup_paths' => [],
                'failure_code' => 'tempfile_unavailable',
            ];
        }
        $target .= '.png';

        try {
            $process = new Process([$binary, $path, '-auto-orient', '-colorspace', 'Gray', $target]);
            $process->setTimeout((int) config('document-ai.ocr.timeout', 120));
            $process->run();

            if (! $process->isSuccessful() || ! is_file($target)) {
                return [
                    'paths' => [],
                    'method' => $method,
                    'pages_count' => null,
                    'cleanup_paths' => [],
                    'failure_code' => 'image_convert_failed',
                ];
            }

            return [
                'paths' => [$target],
                'method' => $method,
                'pages_count' => 1,
                'cleanup_paths' => [$target],
                'failure_code' => null,
            ];
        } catch (Throwable) {
            return [
                'paths' => [],
                'method' => $method,
                'pages_count' => null,
                'cleanup_paths' => [],
                'failure_code' => 'image_convert_exception',
            ];
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

    private function configString(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
