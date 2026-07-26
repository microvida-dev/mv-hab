<?php

declare(strict_types=1);

final class PintChangedFiles
{
    /**
     * @param  list<string>  $files
     */
    public function __construct(
        public readonly string $baseRef,
        public readonly string $mergeBase,
        public readonly array $files,
    ) {}
}

final class PintChangedChecker
{
    public function __construct(
        private readonly string $repositoryRoot,
        private readonly int $chunkSize = 100,
        private readonly ?string $pintBinary = null,
    ) {
        if ($this->chunkSize < 1) {
            throw new InvalidArgumentException('O tamanho dos blocos deve ser superior a zero.');
        }
    }

    public function changedFiles(string $baseRef, string $headRef = 'HEAD'): PintChangedFiles
    {
        $this->assertGitRepository();
        $this->assertRefExists($baseRef);
        $this->assertRefExists($headRef);

        $mergeBase = trim($this->runGit(['merge-base', $baseRef, $headRef]));

        if ($mergeBase === '') {
            throw new RuntimeException("Não foi possível calcular o merge-base para [{$baseRef}].");
        }

        $output = $this->runGit([
            'diff',
            '--name-only',
            '--diff-filter=ACMR',
            '-z',
            $mergeBase.'..'.$headRef,
            '--',
        ]);

        $files = array_values(array_filter(
            explode("\0", $output),
            fn (string $file): bool => $file !== ''
                && str_ends_with($file, '.php')
                && is_file($this->repositoryRoot.'/'.$file),
        ));

        sort($files);

        return new PintChangedFiles($baseRef, $mergeBase, $files);
    }

    public function check(PintChangedFiles $selection): int
    {
        if ($selection->files === []) {
            return 0;
        }

        $binary = $this->pintBinary ?? $this->repositoryRoot.'/vendor/bin/pint';

        if (! is_file($binary) || ! is_executable($binary)) {
            throw new RuntimeException("O executável Pint não está disponível em [{$binary}].");
        }

        foreach (array_chunk($selection->files, $this->chunkSize) as $files) {
            $exitCode = $this->runProcess([$binary, '--test', ...$files]);

            if ($exitCode !== 0) {
                return $exitCode;
            }
        }

        return 0;
    }

    private function assertGitRepository(): void
    {
        if (trim($this->runGit(['rev-parse', '--is-inside-work-tree'])) !== 'true') {
            throw new RuntimeException("A pasta [{$this->repositoryRoot}] não é um repositório Git.");
        }
    }

    private function assertRefExists(string $ref): void
    {
        $this->runGit(['rev-parse', '--verify', $ref.'^{commit}']);
    }

    /**
     * @param  list<string>  $arguments
     */
    private function runGit(array $arguments): string
    {
        [$exitCode, $stdout, $stderr] = $this->process(['git', '-C', $this->repositoryRoot, ...$arguments]);

        if ($exitCode !== 0) {
            $detail = trim($stderr);

            throw new RuntimeException(
                $detail !== ''
                    ? $detail
                    : sprintf('O comando Git terminou com o código %d.', $exitCode),
            );
        }

        return $stdout;
    }

    /**
     * @param  list<string>  $command
     */
    private function runProcess(array $command): int
    {
        [$exitCode, $stdout, $stderr] = $this->process($command);

        if ($stdout !== '') {
            fwrite(STDOUT, $stdout);
        }

        if ($stderr !== '') {
            fwrite(STDERR, $stderr);
        }

        return $exitCode;
    }

    /**
     * @param  list<string>  $command
     * @return array{int, string, string}
     */
    private function process(array $command): array
    {
        $pipes = [];
        $process = proc_open(
            $command,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $this->repositoryRoot,
        );

        if (! is_resource($process)) {
            throw new RuntimeException('Não foi possível iniciar o processo de qualidade.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [
            proc_close($process),
            $stdout !== false ? $stdout : '',
            $stderr !== false ? $stderr : '',
        ];
    }
}

final class PintChangedBaseResolver
{
    public function __construct(private readonly string $repositoryRoot) {}

    /**
     * @param  list<string>  $arguments
     */
    public function resolve(array $arguments): string
    {
        foreach ($arguments as $argument) {
            if ($argument !== '' && $argument !== '--') {
                return $argument;
            }
        }

        foreach (['QUALITY_BASE_REF', 'GITHUB_BASE_REF'] as $environmentVariable) {
            $value = getenv($environmentVariable);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        $configuredBase = $this->configuredBaseRef();

        if ($configuredBase !== null) {
            return $configuredBase;
        }

        throw new InvalidArgumentException(
            'Indique a base: composer quality:pint:changed -- <base-ref>, '
            .'defina QUALITY_BASE_REF/GITHUB_BASE_REF ou configure git config quality.baseRef.',
        );
    }

    private function configuredBaseRef(): ?string
    {
        $pipes = [];
        $process = proc_open(
            ['git', '-C', $this->repositoryRoot, 'config', '--get', 'quality.baseRef'],
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );

        if (! is_resource($process)) {
            throw new RuntimeException('Não foi possível consultar a configuração Git.');
        }

        $stdout = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || $stdout === false || trim($stdout) === '') {
            return null;
        }

        return trim($stdout);
    }
}

function runPintChangedCli(): int
{
    try {
        $repositoryRoot = dirname(__DIR__, 2);
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

        $baseRef = (new PintChangedBaseResolver($repositoryRoot))->resolve($arguments);
        $checker = new PintChangedChecker($repositoryRoot);
        $selection = $checker->changedFiles($baseRef);

        fwrite(STDOUT, sprintf(
            "Pint incremental: %d ficheiro(s) PHP, base %s, merge-base %s.\n",
            count($selection->files),
            $selection->baseRef,
            $selection->mergeBase,
        ));

        foreach ($selection->files as $file) {
            fwrite(STDOUT, " - {$file}\n");
        }

        if ($selection->files === []) {
            fwrite(STDOUT, "Nenhum ficheiro PHP alterado para verificar.\n");

            return 0;
        }

        return $checker->check($selection);
    } catch (Throwable $exception) {
        fwrite(STDERR, 'Erro do Pint incremental: '.$exception->getMessage()."\n");

        return 2;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(runPintChangedCli());
}
