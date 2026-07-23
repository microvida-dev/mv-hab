<?php

declare(strict_types=1);

namespace Tests\Unit\Quality;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use TestIntegrityAuditor;

require_once dirname(__DIR__, 3).'/scripts/quality/audit-test-integrity.php';

final class TestIntegrityAuditScriptTest extends TestCase
{
    private string $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = sys_get_temp_dir().'/mvhab-integrity-'.bin2hex(random_bytes(6));
        mkdir($this->repository.'/tests/Feature', 0777, true);
        $this->git('init', '--quiet');
        $this->git('config', 'user.email', 'quality@example.test');
        $this->git('config', 'user.name', 'MV HAB Quality');

        file_put_contents(
            $this->repository.'/tests/Feature/AuthorizationTest.php',
            "<?php\n// baseline\n",
        );
        $this->git('add', 'tests/Feature/AuthorizationTest.php');
        $this->git('commit', '--quiet', '-m', 'baseline');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->repository);

        parent::tearDown();
    }

    public function test_it_detects_a_prohibited_added_assertion(): void
    {
        $pattern = 'assertTrue'.'(true)';
        file_put_contents(
            $this->repository.'/tests/Feature/AuthorizationTest.php',
            "<?php\n// baseline\n\$this->{$pattern};\n",
        );
        $this->git('add', 'tests/Feature/AuthorizationTest.php');
        $this->git('commit', '--quiet', '-m', 'add prohibited assertion');

        $result = (new TestIntegrityAuditor($this->repository))->audit('HEAD~1');

        $this->assertCount(1, $result->criticalFindings());
        $this->assertSame('tautological_assertion', $result->criticalFindings()[0]->rule);
        $this->assertSame(3, $result->criticalFindings()[0]->line);
    }

    public function test_it_warns_when_a_security_assertion_is_removed(): void
    {
        file_put_contents(
            $this->repository.'/tests/Feature/AuthorizationTest.php',
            "<?php\n\$response->assertForbidden();\n",
        );
        $this->git('add', 'tests/Feature/AuthorizationTest.php');
        $this->git('commit', '--quiet', '-m', 'add assertion');
        file_put_contents(
            $this->repository.'/tests/Feature/AuthorizationTest.php',
            "<?php\n\$response->assertRedirect('/login');\n",
        );
        $this->git('add', 'tests/Feature/AuthorizationTest.php');
        $this->git('commit', '--quiet', '-m', 'replace assertion');

        $result = (new TestIntegrityAuditor($this->repository))->audit('HEAD~1');

        $this->assertCount(0, $result->criticalFindings());
        $this->assertCount(1, $result->warnings());
        $this->assertSame('removed_assertion', $result->warnings()[0]->rule);
    }

    public function test_it_rejects_an_unknown_base_ref(): void
    {
        $this->expectException(RuntimeException::class);

        (new TestIntegrityAuditor($this->repository))->audit('missing-ref');
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
