#!/usr/bin/env php
<?php

use App\Services\Security\SecretPatternScanner;

require __DIR__.'/../vendor/autoload.php';

$basePath = realpath(__DIR__.'/..');

if ($basePath === false) {
    fwrite(STDERR, "Base path unavailable.\n");
    exit(1);
}

$paths = array_slice($argv, 1);
$targets = $paths === [] ? defaultReleaseTargets($basePath) : absoluteTargets($paths, $basePath);
$scanner = new SecretPatternScanner;
$findings = $scanner->scanFiles($targets, $basePath);

if ($findings === []) {
    echo "Release artifact safety scan PASS: no blocked artifacts or sensitive patterns found.\n";
    exit(0);
}

echo 'Release artifact safety scan FAIL: '.count($findings)." finding(s).\n";

foreach ($findings as $finding) {
    $line = isset($finding['line']) ? ':'.$finding['line'] : '';
    echo "[{$finding['severity']}] {$finding['code']} {$finding['path']}{$line} - {$finding['message']}\n";
}

exit(1);

/**
 * @return list<string>
 */
function defaultReleaseTargets(string $basePath): array
{
    $roots = [
        '.gitignore',
        'app',
        'config',
        'database/migrations',
        'database/seeders/CandidateSupportDemoSeeder.php',
        'database/seeders/DemoAlcanenaAffordableRentSeeder.php',
        'database/seeders/MunicipalPilotStagingSeeder.php',
        'docs/08-qa/phase-1-hardening-before-municipal-presentation-report.md',
        'docs/08-qa/qa-37-release-packaging-secrets-hardening-report.md',
        'docs/08-qa/qa-38-queues-scheduler-workers-report.md',
        'docs/08-qa/qa-39-pilot-scope-dossier-sanitization-report.md',
        'docs/08-qa/qa-40-municipal-demo-data-seeder-hardening-report.md',
        'docs/08-qa/phase-2-controlled-municipal-staging-readiness-report.md',
        'docs/08-qa/qa-41-backup-restore-rollback-rehearsal-report.md',
        'docs/08-qa/qa-42-wcag-accessibility-report.md',
        'docs/08-qa/qa-43-alcanena-legal-parameterization-report.md',
        'docs/08-qa/qa-44-municipal-rbac-team-matrix-report.md',
        'docs/11-operacoes',
        'resources',
        'routes',
        'scripts',
        'storage/qa/phase-1-artifact-safety.txt',
        'storage/qa/phase-1-secret-scan.txt',
        'storage/qa/phase-2-artifact-safety.txt',
        'storage/qa/phase-2-secret-scan.txt',
    ];

    return collectTargets($roots, $basePath);
}

/**
 * @param  list<string>  $paths
 * @return list<string>
 */
function absoluteTargets(array $paths, string $basePath): array
{
    return collectTargets($paths, $basePath);
}

/**
 * @param  list<string>  $roots
 * @return list<string>
 */
function collectTargets(array $roots, string $basePath): array
{
    $targets = [];

    foreach ($roots as $root) {
        $path = str_starts_with($root, '/') ? $root : $basePath.'/'.$root;

        if (is_file($path)) {
            $targets[] = $path;

            continue;
        }

        if (! is_dir($path)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                fn (SplFileInfo $file): bool => shouldScanPath($file->getPathname(), $basePath),
            ),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                $targets[] = $file->getPathname();
            }
        }
    }

    return array_values(array_unique($targets));
}

function shouldScanPath(string $path, string $basePath): bool
{
    $relative = ltrim(str_replace($basePath, '', str_replace('\\', '/', $path)), '/');

    foreach ([
        'app/Services/Security/SecretPatternScanner.php',
        'node_modules/',
        'vendor/',
        'storage/app/private/',
        'storage/framework/',
        'storage/logs/',
        'storage/phpstan/',
        'public/build/',
    ] as $blockedPrefix) {
        if (str_starts_with($relative, $blockedPrefix)) {
            return false;
        }
    }

    return true;
}
