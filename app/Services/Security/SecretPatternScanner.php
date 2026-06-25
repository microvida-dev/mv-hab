<?php

namespace App\Services\Security;

use SplFileInfo;

class SecretPatternScanner
{
    /**
     * @return list<array{code: string, severity: string, path: string, message: string, line?: int}>
     */
    public function scanFile(string $path, ?string $displayPath = null): array
    {
        $displayPath ??= $path;
        $findings = $this->scanPath($displayPath);

        if (! is_file($path) || ! is_readable($path)) {
            return $findings;
        }

        if ($this->isBinaryFile($path)) {
            return [
                ...$findings,
                ...$this->scanBinaryPath($displayPath),
            ];
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return $findings;
        }

        return [
            ...$findings,
            ...$this->scanContent($content, $displayPath),
        ];
    }

    /**
     * @param  list<string>  $paths
     * @return list<array{code: string, severity: string, path: string, message: string, line?: int}>
     */
    public function scanFiles(array $paths, ?string $basePath = null): array
    {
        $findings = [];

        foreach ($paths as $path) {
            $displayPath = $basePath !== null ? $this->relativePath($path, $basePath) : $path;
            $findings = [
                ...$findings,
                ...$this->scanFile($path, $displayPath),
            ];
        }

        return $findings;
    }

    /**
     * @return list<array{code: string, severity: string, path: string, message: string}>
     */
    public function scanPath(string $path): array
    {
        $normalized = str_replace('\\', '/', $path);
        $basename = basename($normalized);
        $findings = [];

        if ($basename !== '.env.example' && preg_match('#(^|/)\.env($|\.)#', $normalized) === 1) {
            $findings[] = $this->finding('env_file', $normalized, 'Ficheiro .env nao pode entrar em artefactos de release.');
        }

        if (str_contains($normalized, 'storage/app/private')) {
            $findings[] = $this->finding('private_storage_path', $normalized, 'Storage privado nao pode entrar em artefactos versionaveis.');
        }

        if (preg_match('#\.(?:sql|dump|tar|tar\.gz|zip|key|pem)$#i', $normalized) === 1) {
            $findings[] = $this->finding('blocked_artifact_extension', $normalized, 'Extensao bloqueada para artefactos versionaveis.');
        }

        if (preg_match('#^(?:exports?|backups?)/#i', $normalized) === 1
            || preg_match('#^storage/(?:app/)?exports?/#i', $normalized) === 1
        ) {
            $findings[] = $this->finding('export_or_backup_path', $normalized, 'Exports e backups nao podem ser versionados.');
        }

        return $findings;
    }

    /**
     * @return list<array{code: string, severity: string, path: string, message: string, line: int}>
     */
    public function scanContent(string $content, string $path = 'inline'): array
    {
        $findings = [];

        foreach ($this->contentPatterns() as $pattern) {
            if (preg_match_all($pattern['regex'], $content, $matches, PREG_OFFSET_CAPTURE) === false) {
                continue;
            }

            foreach ($matches[0] as $match) {
                [$value, $offset] = $match;

                if ($pattern['code'] === 'storage_path' && str_ends_with($path, '.php')) {
                    continue;
                }

                if ($this->isAllowedPlaceholder($value)) {
                    continue;
                }

                $findings[] = [
                    ...$this->finding($pattern['code'], $path, $pattern['message'], $pattern['severity']),
                    'line' => $this->lineForOffset($content, (int) $offset),
                ];
            }
        }

        return $findings;
    }

    /**
     * @return list<array{code: string, regex: string, message: string, severity: string}>
     */
    private function contentPatterns(): array
    {
        return [
            [
                'code' => 'app_key',
                'regex' => '/\bAPP_KEY\s*=\s*base64:[A-Za-z0-9+\/=]{32,}/',
                'message' => 'APP_KEY real detetado.',
                'severity' => 'critical',
            ],
            [
                'code' => 'app_debug_true',
                'regex' => '/\bAPP_DEBUG\s*=\s*true\b/i',
                'message' => 'APP_DEBUG=true nao e aceite em artefactos staging/demo.',
                'severity' => 'high',
            ],
            [
                'code' => 'db_password',
                'regex' => '/\bDB_PASSWORD\s*=\s*["\']?(?!<|REDACTED\b|redacted\b|placeholder\b|CHANGE_ME\b|changeme\b|example\b|dummy\b|\*{3,})([^\s"\']{8,})["\']?/i',
                'message' => 'DB_PASSWORD com valor nao placeholder detetado.',
                'severity' => 'critical',
            ],
            [
                'code' => 'private_key',
                'regex' => '/-----BEGIN [A-Z ]*PRIVATE KEY-----/',
                'message' => 'Chave privada detetada.',
                'severity' => 'critical',
            ],
            [
                'code' => 'token',
                'regex' => '/\b(?:token|secret|api[_-]?key|api[_-]?token|access[_-]?token|refresh[_-]?token)\s*[:=]\s*["\']?(?!<|REDACTED\b|redacted\b|placeholder\b|example\b|dummy\b)([A-Za-z0-9_.\-]{20,})["\']?/i',
                'message' => 'Token ou segredo com formato real detetado.',
                'severity' => 'critical',
            ],
            [
                'code' => 'local_user_path',
                'regex' => '#/Users/[^ \n\r\t\'")]+#',
                'message' => 'Path local pessoal /Users/ detetado.',
                'severity' => 'high',
            ],
            [
                'code' => 'storage_path',
                'regex' => '/\bstorage_path\s*\(/',
                'message' => 'storage_path exposto em artefacto textual.',
                'severity' => 'medium',
            ],
            [
                'code' => 'nif_like',
                'regex' => '/\b[1235789]\d{8}\b/',
                'message' => 'Padrao compativel com NIF detetado.',
                'severity' => 'high',
            ],
            [
                'code' => 'niss_like',
                'regex' => '/\b1\d{10}\b/',
                'message' => 'Padrao compativel com NISS detetado.',
                'severity' => 'high',
            ],
            [
                'code' => 'iban_pt_like',
                'regex' => '/\bPT50\d{21}\b/i',
                'message' => 'Padrao compativel com IBAN portugues detetado.',
                'severity' => 'high',
            ],
            [
                'code' => 'address_like',
                'regex' => '/\b(?:Rua|Avenida|Travessa|Praceta|Largo)\s+[A-ZÁÉÍÓÚÂÊÔÃÕÇ][^,\n\r]{4,}\s+\d{1,4}\b/u',
                'message' => 'Padrao compativel com morada detetado.',
                'severity' => 'medium',
            ],
        ];
    }

    /**
     * @return array{code: string, severity: string, path: string, message: string}
     */
    private function finding(string $code, string $path, string $message, string $severity = 'high'): array
    {
        return [
            'code' => $code,
            'severity' => $severity,
            'path' => $path,
            'message' => $message,
        ];
    }

    /**
     * @return list<array{code: string, severity: string, path: string, message: string}>
     */
    private function scanBinaryPath(string $path): array
    {
        $extension = strtolower((new SplFileInfo($path))->getExtension());

        if (! in_array($extension, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'tiff'], true)) {
            return [];
        }

        if (preg_match('#(^|/)(docs/00-fontes|public|resources|tests/Fixtures)(/|$)#', $path) === 1) {
            return [];
        }

        return [
            $this->finding('document_binary_candidate', $path, 'Documento binario potencialmente real detetado em superficie versionavel.', 'high'),
        ];
    }

    private function isBinaryFile(string $path): bool
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $sample = fread($handle, 512);
        fclose($handle);

        return is_string($sample) && str_contains($sample, "\0");
    }

    private function lineForOffset(string $content, int $offset): int
    {
        return substr_count(substr($content, 0, $offset), "\n") + 1;
    }

    private function relativePath(string $path, string $basePath): string
    {
        $base = rtrim(str_replace('\\', '/', $basePath), '/').'/';
        $normalized = str_replace('\\', '/', $path);

        return str_starts_with($normalized, $base)
            ? substr($normalized, strlen($base))
            : $normalized;
    }

    private function isAllowedPlaceholder(string $value): bool
    {
        return preg_match('/(<[^>]+>|REDACTED|redacted|placeholder|example|dummy|CHANGE_ME|\*{3,})/i', $value) === 1;
    }
}
