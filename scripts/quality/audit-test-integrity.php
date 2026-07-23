<?php

declare(strict_types=1);

final class TestIntegrityFinding
{
    public function __construct(
        public readonly string $severity,
        public readonly string $file,
        public readonly int $line,
        public readonly string $rule,
        public readonly string $message,
    ) {}

    public function format(): string
    {
        return sprintf(
            '%s:%d [%s] %s (%s)',
            $this->file,
            $this->line,
            strtoupper($this->severity),
            $this->message,
            $this->rule,
        );
    }
}

final class TestIntegrityAuditResult
{
    /**
     * @param  list<string>  $files
     * @param  list<TestIntegrityFinding>  $findings
     */
    public function __construct(
        public readonly string $baseRef,
        public readonly string $mergeBase,
        public readonly array $files,
        public readonly array $findings,
    ) {}

    /** @return list<TestIntegrityFinding> */
    public function criticalFindings(): array
    {
        return array_values(array_filter(
            $this->findings,
            static fn (TestIntegrityFinding $finding): bool => $finding->severity === 'critical',
        ));
    }

    /** @return list<TestIntegrityFinding> */
    public function warnings(): array
    {
        return array_values(array_filter(
            $this->findings,
            static fn (TestIntegrityFinding $finding): bool => $finding->severity === 'warning',
        ));
    }
}

final class TestIntegrityAuditor
{
    /**
     * Patterns are evaluated only against lines added since the merge-base.
     *
     * @var array<string, array{pattern: string, message: string}>
     */
    private const CRITICAL_PATTERNS = [
        'tautological_assertion' => [
            'pattern' => '/\\bassertTrue\\s*\\(\\s*true\\s*\\)/i',
            'message' => 'Foi adicionada uma asserção tautológica.',
        ],
        'skipped_test' => [
            'pattern' => '/\\bmarkTestSkipped\\s*\\(/i',
            'message' => 'Foi adicionado um teste ignorado.',
        ],
        'middleware_disabled' => [
            'pattern' => '/\\b(?:withoutMiddleware|disableMiddleware)\\s*\\(/i',
            'message' => 'Foi desativado middleware num teste funcional.',
        ],
        'expected_server_error' => [
            'pattern' => '/\\bassertStatus\\s*\\(\\s*500\\s*\\)/i',
            'message' => 'Foi aceite um erro HTTP 500 como resultado do teste.',
        ],
        'gate_mock' => [
            'pattern' => '/(?:Gate::shouldReceive|mock\\s*\\([^\\n]*Gate(?:Interface)?::class)/i',
            'message' => 'Foi mockado o Gate num teste de autorização.',
        ],
        'policy_mock' => [
            'pattern' => '/(?:mock|shouldReceive)\\s*\\([^\\n]*Policy(?:::class)?/i',
            'message' => 'Foi mockada uma Policy num teste de autorização.',
        ],
        'permission_mock' => [
            'pattern' => '/(?:User::shouldReceive|shouldReceive\\s*\\([^\\n]*hasPermission)/i',
            'message' => 'Foi mockada a resolução efetiva de permissões.',
        ],
        'all_features_helper' => [
            'pattern' => '/\\benableAll(?:Municipal)?Features\\s*\\(/i',
            'message' => 'Foi adicionado um helper que ativa todas as funcionalidades.',
        ],
    ];

    /**
     * @var array<string, array{pattern: string, message: string}>
     */
    private const WARNING_PATTERNS = [
        'events_without_outcome' => [
            'pattern' => '/\\bexpectsEvents\\s*\\(/i',
            'message' => 'Rever se a expectativa de evento também valida o resultado funcional.',
        ],
        'wildcard_permission' => [
            'pattern' => "/['\"]\\*['\"]/",
            'message' => 'Rever a justificação para uma permission wildcard em fixture.',
        ],
    ];

    /**
     * @var array<string, string>
     */
    private const REMOVED_ASSERTION_PATTERNS = [
        'assertDatabaseHas' => 'Foi removida uma validação de persistência.',
        'assertDatabaseMissing' => 'Foi removida uma validação de ausência de persistência.',
        'assertForbidden' => 'Foi removida uma validação de acesso recusado.',
        'assertStatus(403)' => 'Foi removida uma validação explícita de HTTP 403.',
        'assertNotFound' => 'Foi removida uma validação de recurso ocultado/inexistente.',
        'expectException' => 'Foi removida uma expectativa de exceção.',
        'assertDispatched' => 'Foi removida uma validação de dispatch.',
        'assertNotDispatched' => 'Foi removida uma validação de ausência de dispatch.',
        'assertSame' => 'Foi removida uma comparação estrita.',
        'assertCount' => 'Foi removida uma validação de cardinalidade.',
        'assertRedirectToRoute' => 'Foi removida uma validação explícita do destino do redirect.',
    ];

    public function __construct(private readonly string $repositoryRoot) {}

    public function audit(string $baseRef, string $headRef = 'HEAD'): TestIntegrityAuditResult
    {
        $this->assertGitRepository();
        $this->assertRefExists($baseRef);
        $this->assertRefExists($headRef);

        $mergeBase = trim($this->runGit(['merge-base', $baseRef, $headRef]));
        if ($mergeBase === '') {
            throw new RuntimeException("Não foi possível calcular o merge-base para [{$baseRef}].");
        }

        $trackedFiles = $this->changedTestFiles($mergeBase, $headRef);
        $untrackedFiles = $headRef === 'HEAD' ? $this->untrackedTestFiles() : [];
        $files = array_values(array_unique([...$trackedFiles, ...$untrackedFiles]));
        sort($files);
        $findings = [];

        foreach ($files as $file) {
            $isUntracked = in_array($file, $untrackedFiles, true);

            foreach ($this->changedLines($mergeBase, $headRef, $file, $isUntracked) as $change) {
                if ($change['kind'] === 'added') {
                    $findings = [
                        ...$findings,
                        ...$this->findAddedLineIssues($file, $change['line'], $change['content']),
                    ];

                    continue;
                }

                $findings = [
                    ...$findings,
                    ...$this->findRemovedAssertionWarnings($file, $change['line'], $change['content']),
                ];
            }
        }

        return new TestIntegrityAuditResult($baseRef, $mergeBase, $files, $findings);
    }

    private function assertGitRepository(): void
    {
        $inside = trim($this->runGit(['rev-parse', '--is-inside-work-tree']));

        if ($inside !== 'true') {
            throw new RuntimeException("A pasta [{$this->repositoryRoot}] não é um repositório Git.");
        }
    }

    private function assertRefExists(string $ref): void
    {
        $this->runGit(['rev-parse', '--verify', $ref.'^{commit}']);
    }

    /** @return list<string> */
    private function changedTestFiles(string $mergeBase, string $headRef): array
    {
        $output = $this->runGit(array_values(array_filter([
            'diff',
            '--name-only',
            '--diff-filter=ACMR',
            '-z',
            $headRef === 'HEAD' ? $mergeBase : $mergeBase.'..'.$headRef,
            '--',
            'tests',
        ], static fn (string $argument): bool => $argument !== '')));

        if ($output === '') {
            return [];
        }

        return array_values(array_filter(
            explode("\0", $output),
            static fn (string $file): bool => $file !== ''
                && str_ends_with($file, '.php'),
        ));
    }

    /** @return list<string> */
    private function untrackedTestFiles(): array
    {
        $output = $this->runGit([
            'ls-files',
            '--others',
            '--exclude-standard',
            '-z',
            '--',
            'tests',
        ]);

        if ($output === '') {
            return [];
        }

        return array_values(array_filter(
            explode("\0", $output),
            static fn (string $file): bool => $file !== ''
                && str_ends_with($file, '.php'),
        ));
    }

    /**
     * @return list<array{kind: 'added'|'removed', line: int, content: string}>
     */
    private function changedLines(
        string $mergeBase,
        string $headRef,
        string $file,
        bool $isUntracked,
    ): array {
        if ($isUntracked) {
            $contents = file($this->repositoryRoot.'/'.$file, FILE_IGNORE_NEW_LINES);

            if ($contents === false) {
                throw new RuntimeException("Não foi possível ler o ficheiro [{$file}].");
            }

            return array_map(
                static fn (string $content, int $index): array => [
                    'kind' => 'added',
                    'line' => $index + 1,
                    'content' => $content,
                ],
                $contents,
                array_keys($contents),
            );
        }

        $diff = $this->runGit(array_values(array_filter([
            'diff',
            '--no-color',
            '--unified=0',
            $headRef === 'HEAD' ? $mergeBase : $mergeBase.'..'.$headRef,
            '--',
            $file,
        ], static fn (string $argument): bool => $argument !== '')));

        $changes = [];
        $oldLine = 0;
        $newLine = 0;

        foreach (preg_split('/\\R/', $diff) ?: [] as $line) {
            if (preg_match('/^@@ -(\\d+)(?:,\\d+)? \\+(\\d+)(?:,\\d+)? @@/', $line, $matches) === 1) {
                $oldLine = (int) $matches[1];
                $newLine = (int) $matches[2];

                continue;
            }

            if (str_starts_with($line, '+++') || str_starts_with($line, '---')) {
                continue;
            }

            if (str_starts_with($line, '+')) {
                $changes[] = [
                    'kind' => 'added',
                    'line' => $newLine,
                    'content' => substr($line, 1),
                ];
                $newLine++;

                continue;
            }

            if (str_starts_with($line, '-')) {
                $changes[] = [
                    'kind' => 'removed',
                    'line' => $oldLine,
                    'content' => substr($line, 1),
                ];
                $oldLine++;

                continue;
            }

            if (str_starts_with($line, ' ')) {
                $oldLine++;
                $newLine++;
            }
        }

        return $changes;
    }

    /** @return list<TestIntegrityFinding> */
    private function findAddedLineIssues(string $file, int $line, string $content): array
    {
        $findings = [];

        foreach (self::CRITICAL_PATTERNS as $rule => $definition) {
            if (preg_match($definition['pattern'], $content) === 1) {
                $findings[] = new TestIntegrityFinding(
                    'critical',
                    $file,
                    $line,
                    $rule,
                    $definition['message'],
                );
            }
        }

        if (
            basename($file) !== 'FeatureKeyTest.php'
            && str_contains($content, 'FeatureKey::cases()')
        ) {
            $findings[] = new TestIntegrityFinding(
                'critical',
                $file,
                $line,
                'all_features_activation',
                'FeatureKey::cases() não pode ser usada como ativação normal de fixtures.',
            );
        }

        if (
            str_contains($file, '/Feature/')
            && (
                str_contains($content, 'mock(MunicipalityEntitlementService::class')
                || str_contains($content, 'mock(MunicipalRecordScopeService::class')
            )
        ) {
            $findings[] = new TestIntegrityFinding(
                'critical',
                $file,
                $line,
                'municipal_security_service_mock',
                'Foi mockado um serviço municipal de segurança num teste funcional.',
            );
        }

        foreach (self::WARNING_PATTERNS as $rule => $definition) {
            if (preg_match($definition['pattern'], $content) === 1) {
                $findings[] = new TestIntegrityFinding(
                    'warning',
                    $file,
                    $line,
                    $rule,
                    $definition['message'],
                );
            }
        }

        return $findings;
    }

    /** @return list<TestIntegrityFinding> */
    private function findRemovedAssertionWarnings(string $file, int $line, string $content): array
    {
        $findings = [];

        foreach (self::REMOVED_ASSERTION_PATTERNS as $assertion => $message) {
            if (str_contains($content, $assertion)) {
                $findings[] = new TestIntegrityFinding(
                    'warning',
                    $file,
                    $line,
                    'removed_assertion',
                    $message.' Confirme a substituição no cenário.',
                );
            }
        }

        return $findings;
    }

    /**
     * @param  list<string>  $arguments
     */
    private function runGit(array $arguments): string
    {
        $command = ['git', '-C', $this->repositoryRoot, ...$arguments];
        $escaped = implode(' ', array_map('escapeshellarg', $command));
        $pipes = [];
        $process = proc_open(
            $escaped,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );

        if (! is_resource($process)) {
            throw new RuntimeException('Não foi possível iniciar o processo Git.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $detail = trim($stderr !== false ? $stderr : '');

            throw new RuntimeException(
                $detail !== ''
                    ? $detail
                    : sprintf('O comando Git terminou com o código %d.', $exitCode),
            );
        }

        return $stdout !== false ? $stdout : '';
    }
}

/**
 * @param  list<string>  $arguments
 */
function testIntegrityBaseRef(array $arguments): string
{
    foreach ($arguments as $argument) {
        if ($argument !== '' && $argument !== '--') {
            return $argument;
        }
    }

    $environmentBase = getenv('QUALITY_TEST_BASE_REF');

    if (is_string($environmentBase) && trim($environmentBase) !== '') {
        return trim($environmentBase);
    }

    throw new InvalidArgumentException(
        'Indique a base: composer quality:tests:integrity -- <base-ref> '
        .'ou defina QUALITY_TEST_BASE_REF.',
    );
}

function runTestIntegrityAuditCli(): int
{
    try {
        /** @var list<string> $arguments */
        $arguments = [];
        $serverArguments = $_SERVER['argv'] ?? null;

        if (is_array($serverArguments)) {
            foreach ($serverArguments as $index => $argument) {
                if ($index > 0 && is_string($argument)) {
                    $arguments[] = $argument;
                }
            }
        }

        $baseRef = testIntegrityBaseRef($arguments);
        $repositoryRoot = dirname(__DIR__, 2);
        $result = (new TestIntegrityAuditor($repositoryRoot))->audit($baseRef);

        fwrite(STDOUT, sprintf(
            "Auditoria de integridade: %d ficheiro(s), base %s, merge-base %s.\n",
            count($result->files),
            $result->baseRef,
            $result->mergeBase,
        ));

        foreach ($result->findings as $finding) {
            fwrite(STDOUT, $finding->format()."\n");
        }

        fwrite(STDOUT, sprintf(
            "Resultado: %d violação(ões) crítica(s), %d aviso(s).\n",
            count($result->criticalFindings()),
            count($result->warnings()),
        ));

        return $result->criticalFindings() === [] ? 0 : 1;
    } catch (Throwable $exception) {
        fwrite(STDERR, 'Erro da auditoria: '.$exception->getMessage()."\n");

        return 2;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(runTestIntegrityAuditCli());
}
