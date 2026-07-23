<?php

declare(strict_types=1);

namespace Tests\Unit\Quality;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PintChangedBaseResolver;
use PintChangedChecker;
use RuntimeException;

require_once dirname(__DIR__, 3).'/scripts/quality/pint-changed.php';

final class PintChangedScriptTest extends TestCase
{
    private string $repository;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('QUALITY_BASE_REF');
        putenv('GITHUB_BASE_REF');

        $this->repository = sys_get_temp_dir().'/mvhab-pint-'.bin2hex(random_bytes(6));
        mkdir($this->repository.'/app', 0777, true);
        mkdir($this->repository.'/vendor/bin', 0777, true);
        $this->git('init', '--quiet');
        $this->git('config', 'user.email', 'quality@example.test');
        $this->git('config', 'user.name', 'MV HAB Quality');

        file_put_contents($this->repository.'/app/Baseline.php', "<?php\n");
        $this->git('add', 'app/Baseline.php');
        $this->git('commit', '--quiet', '-m', 'baseline');
    }

    protected function tearDown(): void
    {
        putenv('QUALITY_BASE_REF');
        putenv('GITHUB_BASE_REF');
        $this->removeDirectory($this->repository);

        parent::tearDown();
    }

    public function test_it_selects_only_existing_changed_php_files_with_nul_safe_names(): void
    {
        file_put_contents($this->repository.'/app/Baseline.php', "<?php\n// changed\n");
        file_put_contents($this->repository.'/app/With space.php', "<?php\n");
        file_put_contents($this->repository.'/README.md', "changed\n");
        $this->git('add', 'app/Baseline.php', 'app/With space.php', 'README.md');
        $this->git('commit', '--quiet', '-m', 'changed files');

        $selection = (new PintChangedChecker($this->repository))->changedFiles('HEAD~1');

        $this->assertSame(
            ['app/Baseline.php', 'app/With space.php'],
            $selection->files,
        );
        $this->assertSame(
            trim($this->gitOutput('rev-parse', 'HEAD~1')),
            $selection->mergeBase,
        );
    }

    public function test_it_runs_pint_in_chunks_and_preserves_file_names(): void
    {
        file_put_contents($this->repository.'/app/Baseline.php', "<?php\n// changed\n");
        file_put_contents($this->repository.'/app/With space.php', "<?php\n");
        $this->git('add', 'app/Baseline.php', 'app/With space.php');
        $this->git('commit', '--quiet', '-m', 'changed php files');

        $binary = $this->repository.'/vendor/bin/pint';
        file_put_contents(
            $binary,
            "#!/bin/sh\nprintf '%s\\n' \"\$@\" >> \"".addslashes($this->repository)."/pint-arguments.log\"\n",
        );
        chmod($binary, 0755);

        $checker = new PintChangedChecker($this->repository, 1, $binary);
        $selection = $checker->changedFiles('HEAD~1');

        $this->assertSame(0, $checker->check($selection));
        $this->assertSame(
            [
                '--test',
                'app/Baseline.php',
                '--test',
                'app/With space.php',
            ],
            file($this->repository.'/pint-arguments.log', FILE_IGNORE_NEW_LINES),
        );
    }

    public function test_it_rejects_an_unknown_base_ref(): void
    {
        $this->expectException(RuntimeException::class);

        (new PintChangedChecker($this->repository))->changedFiles('missing-ref');
    }

    public function test_base_resolver_requires_an_explicit_or_configured_source(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PintChangedBaseResolver($this->repository))->resolve([]);
    }

    public function test_base_resolver_uses_documented_precedence(): void
    {
        $resolver = new PintChangedBaseResolver($this->repository);
        putenv('QUALITY_BASE_REF=quality-ref');
        putenv('GITHUB_BASE_REF=github-ref');
        $this->git('config', 'quality.baseRef', 'configured-ref');

        $this->assertSame('argument-ref', $resolver->resolve(['--', 'argument-ref']));
        $this->assertSame('quality-ref', $resolver->resolve([]));

        putenv('QUALITY_BASE_REF');
        $this->assertSame('github-ref', $resolver->resolve([]));

        putenv('GITHUB_BASE_REF');
        $this->assertSame('configured-ref', $resolver->resolve([]));
    }

    private function git(string ...$arguments): void
    {
        $command = implode(' ', array_map(
            'escapeshellarg',
            ['git', '-C', $this->repository, ...$arguments],
        ));
        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
    }

    private function gitOutput(string ...$arguments): string
    {
        $command = implode(' ', array_map(
            'escapeshellarg',
            ['git', '-C', $this->repository, ...$arguments],
        ));
        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        return implode("\n", $output);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::SKIP_DOTS,
            ),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());

                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
