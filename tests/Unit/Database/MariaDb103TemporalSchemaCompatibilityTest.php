<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;

final class MariaDb103TemporalSchemaCompatibilityTest extends TestCase
{
    public function test_migrations_do_not_declare_required_implicit_timestamp_columns(): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $migrationFiles = glob($projectRoot.'/database/migrations/*.php');

        if ($migrationFiles === false || $migrationFiles === []) {
            self::fail('Não foi possível localizar as migrations da aplicação.');
        }

        $violations = [];

        foreach ($migrationFiles as $migrationFile) {
            $contents = file_get_contents($migrationFile);

            if ($contents === false) {
                self::fail(sprintf(
                    'Não foi possível ler a migration %s.',
                    $migrationFile,
                ));
            }

            $matchCount = preg_match_all(
                '/\$table->timestamp(?:Tz)?\([^;]+;/s',
                $contents,
                $matches,
                PREG_OFFSET_CAPTURE,
            );

            if ($matchCount === false) {
                self::fail(sprintf(
                    'Não foi possível analisar a migration %s.',
                    $migrationFile,
                ));
            }

            foreach ($matches[0] as [$statement, $offset]) {
                if (
                    str_contains($statement, '->nullable()')
                    || str_contains($statement, '->useCurrent()')
                ) {
                    continue;
                }

                $line = substr_count(
                    substr($contents, 0, $offset),
                    "\n",
                ) + 1;

                $normalizedStatement = preg_replace(
                    '/\s+/',
                    ' ',
                    trim($statement),
                ) ?? trim($statement);

                $violations[] = sprintf(
                    '%s:%d:%s',
                    str_replace($projectRoot.'/', '', $migrationFile),
                    $line,
                    $normalizedStatement,
                );
            }
        }

        self::assertSame(
            [],
            $violations,
            "Foram encontrados timestamp()/timestampTz() obrigatórios sem nullable() nem useCurrent():\n".
            implode("\n", $violations),
        );
    }
}
