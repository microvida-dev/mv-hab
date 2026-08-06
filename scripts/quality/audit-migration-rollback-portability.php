<?php

declare(strict_types=1);

final class MigrationRollbackPortabilityFinding
{
    public function __construct(
        public readonly string $file,
        public readonly int $line,
        public readonly string $reference,
        public readonly string $message,
    ) {}
}

final class MigrationRollbackPortabilityAuditResult
{
    /**
     * @param  list<string>  $files
     * @param  list<MigrationRollbackPortabilityFinding>  $findings
     */
    public function __construct(
        public readonly array $files,
        public readonly array $findings,
    ) {}

    public function passed(): bool
    {
        return $this->findings === [];
    }
}

final class MigrationRollbackPortabilityAuditor
{
    /**
     * @var array<string, array<string, string>>
     */
    private const REVIEWED_NAMED_DROPS = [
        'database/migrations/2026_07_27_000042_add_compatible_housing_preference_context.php' => [
            'hp_regulatory_snapshot_fk' => 'Executada apenas quando o driver não é SQLite.',
            'hp_legacy_preference_fk' => 'Executada apenas quando o driver não é SQLite.',
        ],
        'database/migrations/2026_07_31_000050_align_correction_requests_with_published_results.php' => [
            'corr_requests_publication_result_fk' => 'O rollback SQLite retorna antes desta operação e reconstrói a tabela.',
        ],
        'database/migrations/2026_08_01_000051_add_candidate_correction_workspace_fields.php' => [
            'document_versions_replaces_fk' => 'Executada apenas quando o driver não é SQLite.',
            'corr_responses_document_version_fk' => 'Executada apenas quando o driver não é SQLite.',
            'corr_items_source_document_fk' => 'Executada apenas quando o driver não é SQLite.',
            'corr_responses_request_fk' => 'Executada apenas quando o driver não é SQLite.',
        ],
        'database/migrations/2026_08_01_000053_add_correction_revalidation_controls.php' => [
            'corr_requests_revalidation_projector_fk' => 'O ramo SQLite usa as colunas da constraint.',
            'corr_requests_revalidation_result_fk' => 'O ramo SQLite usa as colunas da constraint.',
            'corr_requests_revalidation_starter_fk' => 'O ramo SQLite usa as colunas da constraint.',
            'review_batches_correction_request_fk' => 'O ramo SQLite usa as colunas da constraint.',
        ],
        'database/migrations/2026_08_01_000054_extend_report_exports_for_temporal_application_results.php' => [
            're_municipality_fk' => 'O ramo SQLite usa as colunas da constraint.',
            're_contest_fk' => 'O ramo SQLite usa as colunas da constraint.',
        ],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const REVIEWED_DYNAMIC_DROPS = [
        'database/migrations/2026_07_26_005952_add_municipal_scope_to_visit_domain_tables.php' => [
            '$foreignKey' => 'Executada apenas após o ramo SQLite remover a constraint pelas colunas e retornar.',
        ],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const REVIEWED_VENDOR_METADATA = [
        'database/migrations/2026_07_26_005952_add_municipal_scope_to_visit_domain_tables.php' => [
            'information_schema.TABLE_CONSTRAINTS' => 'Usada apenas no caminho MySQL/MariaDB após retorno explícito do ramo SQLite.',
            'information_schema.STATISTICS' => 'Usada apenas no caminho MySQL/MariaDB após retorno explícito do ramo SQLite.',
        ],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const REVIEWED_REBUILD_ORDER = [
        'database/migrations/2026_07_27_000042_add_compatible_housing_preference_context.php' => [
            'housing_preferences::dropIndex' => 'A migration possui um ramo SQLite específico já validado por rollback integral.',
            'housing_preferences::dropUnique' => 'A migration possui um ramo SQLite específico já validado por rollback integral.',
        ],
        'database/migrations/2026_07_31_000050_align_correction_requests_with_published_results.php' => [
            'correction_requests::dropUnique' => 'O ramo SQLite retorna antes deste bloco específico de MySQL/MariaDB.',
            'correction_requests::dropIndex' => 'O ramo SQLite retorna antes deste bloco específico de MySQL/MariaDB.',
        ],
        'database/migrations/2026_08_01_000051_add_candidate_correction_workspace_fields.php' => [
            'document_versions::dropIndex' => 'A migration possui reconstruções SQLite explícitas já validadas por rollback integral.',
            'correction_responses::dropUnique' => 'A migration possui reconstruções SQLite explícitas já validadas por rollback integral.',
            'correction_responses::dropIndex' => 'A migration possui reconstruções SQLite explícitas já validadas por rollback integral.',
            'correction_request_items::dropIndex' => 'A migration possui reconstruções SQLite explícitas já validadas por rollback integral.',
        ],
        'database/migrations/2026_08_01_000053_add_correction_revalidation_controls.php' => [
            'correction_requests::dropUnique' => 'A migration possui um ramo SQLite específico já validado por rollback integral.',
            'correction_requests::dropIndex' => 'A migration possui um ramo SQLite específico já validado por rollback integral.',
            'application_review_batches::dropUnique' => 'A migration possui um ramo SQLite específico já validado por rollback integral.',
        ],
        'database/migrations/2026_08_01_000054_extend_report_exports_for_temporal_application_results.php' => [
            'report_exports::dropIndex' => 'A migration possui um ramo SQLite específico já validado por rollback integral.',
            'report_exports::dropUnique' => 'A migration possui um ramo SQLite específico já validado por rollback integral.',
        ],
    ];

    public function __construct(private readonly string $repositoryRoot) {}

    public function audit(): MigrationRollbackPortabilityAuditResult
    {
        $migrationDirectory = $this->repositoryRoot.'/database/migrations';

        if (! is_dir($migrationDirectory)) {
            throw new RuntimeException("Diretório de migrations inexistente: [{$migrationDirectory}].");
        }

        $files = glob($migrationDirectory.'/*.php') ?: [];
        sort($files);
        $relativeFiles = [];
        $findings = [];
        $reviewedNamed = [];
        $reviewedDynamic = [];
        $reviewedMetadata = [];
        $reviewedRebuildOrder = [];
        $migrationSources = [];

        foreach ($files as $file) {
            $relativeFile = $this->relativePath($file);
            $relativeFiles[] = $relativeFile;
            $source = file_get_contents($file);

            if ($source === false) {
                throw new RuntimeException("Não foi possível ler [{$relativeFile}].");
            }

            $migrationSources[$relativeFile] = $source;

            foreach ($this->namedDropForeignCalls($source) as $call) {
                $reviewReason = self::REVIEWED_NAMED_DROPS[$relativeFile][$call['reference']] ?? null;

                if ($reviewReason !== null) {
                    $reviewedNamed[$relativeFile][$call['reference']] = true;

                    continue;
                }

                $findings[] = new MigrationRollbackPortabilityFinding(
                    $relativeFile,
                    $call['line'],
                    $call['reference'],
                    'dropForeign() por nome não é portável para SQLite; use colunas ou um ramo explícito por driver.',
                );
            }

            foreach ($this->dynamicDropForeignCalls($source) as $call) {
                $reviewReason = self::REVIEWED_DYNAMIC_DROPS[$relativeFile][$call['reference']] ?? null;

                if ($reviewReason !== null) {
                    $reviewedDynamic[$relativeFile][$call['reference']] = true;

                    continue;
                }

                $findings[] = new MigrationRollbackPortabilityFinding(
                    $relativeFile,
                    $call['line'],
                    $call['reference'],
                    'dropForeign() dinâmico exige revisão explícita do ramo SQLite e allowlist estrita.',
                );
            }

            foreach ($this->vendorMetadataReferences($source) as $reference) {
                $reviewReason = self::REVIEWED_VENDOR_METADATA[$relativeFile][$reference['reference']] ?? null;

                if ($reviewReason !== null) {
                    $reviewedMetadata[$relativeFile][$reference['reference']] = true;

                    continue;
                }

                $findings[] = new MigrationRollbackPortabilityFinding(
                    $relativeFile,
                    $reference['line'],
                    $reference['reference'],
                    'Acesso direto a metadados específicos do fornecedor exige ramo explícito por driver e revisão.',
                );
            }

            foreach ($this->unscopedIndexRepairHelpers($source) as $helper) {
                $findings[] = new MigrationRollbackPortabilityFinding(
                    $relativeFile,
                    $helper['line'],
                    $helper['reference'],
                    'Um helper que preserva ou recria índices durante rollback deve limitar-se explicitamente ao driver aplicável.',
                );
            }

            foreach ($this->indexedColumnsDroppedWithoutIndexRemoval($source) as $index) {
                $findings[] = new MigrationRollbackPortabilityFinding(
                    $relativeFile,
                    $index['line'],
                    $index['reference'],
                    'Uma coluna indexada é removida sem eliminar primeiro o respetivo índice ou constraint unique.',
                );
            }

            foreach ($this->indexDropsAfterTableRebuild($source) as $operation) {
                $reviewReason = self::REVIEWED_REBUILD_ORDER[$relativeFile][$operation['reference']] ?? null;

                if ($reviewReason !== null) {
                    $reviewedRebuildOrder[$relativeFile][$operation['reference']] = true;

                    continue;
                }

                $findings[] = new MigrationRollbackPortabilityFinding(
                    $relativeFile,
                    $operation['line'],
                    $operation['reference'],
                    'Índices e constraints unique devem ser removidos antes de operações que reconstruam a tabela em SQLite.',
                );
            }
        }

        foreach ($this->crossMigrationIndexOwnershipConflicts($migrationSources) as $conflict) {
            $findings[] = new MigrationRollbackPortabilityFinding(
                $conflict['file'],
                $conflict['line'],
                $conflict['reference'],
                'Uma migration de reparação não pode remover no down() um índice canonicamente criado por uma migration anterior.',
            );
        }

        $this->appendStaleAllowlistFindings(self::REVIEWED_NAMED_DROPS, $reviewedNamed, 'named-drop', $findings);
        $this->appendStaleAllowlistFindings(self::REVIEWED_DYNAMIC_DROPS, $reviewedDynamic, 'dynamic-drop', $findings);
        $this->appendStaleAllowlistFindings(self::REVIEWED_VENDOR_METADATA, $reviewedMetadata, 'vendor-metadata', $findings);
        $this->appendStaleAllowlistFindings(self::REVIEWED_REBUILD_ORDER, $reviewedRebuildOrder, 'rebuild-order', $findings);

        return new MigrationRollbackPortabilityAuditResult($relativeFiles, $findings);
    }

    /**
     * @param  array<string, string>  $sources
     * @return list<array{file: string, line: int, reference: string}>
     */
    private function crossMigrationIndexOwnershipConflicts(array $sources): array
    {
        $canonicalOwners = [];
        $findings = [];

        foreach ($sources as $file => $source) {
            $up = $this->methodBody($source, 'up');
            $down = $this->methodBody($source, 'down');
            $restoredIndexes = [];

            if ($up !== null) {
                foreach ($this->schemaTableBlocks($up['body'], $up['line']) as $block) {
                    foreach ($this->indexDefinitions($block['table'], $block['body'], $block['line']) as $index) {
                        $reference = $block['table'].'::'.$index['name'];

                        if (isset($canonicalOwners[$reference])) {
                            $restoredIndexes[$reference] = true;

                            continue;
                        }

                        $canonicalOwners[$reference] = $file;
                    }
                }
            }

            if ($restoredIndexes === [] || $down === null) {
                continue;
            }

            foreach ($this->schemaTableBlocks($down['body'], $down['line']) as $block) {
                foreach ($this->dropIndexReferences($block['table'], $block['body']) as $index) {
                    $reference = $block['table'].'::'.$index;

                    if (! isset($restoredIndexes[$reference])) {
                        continue;
                    }

                    $findings[] = [
                        'file' => $file,
                        'line' => $block['line'],
                        'reference' => $reference,
                    ];
                }
            }
        }

        return $findings;
    }

    /**
     * @param  array<string, array<string, string>>  $allowlist
     * @param  array<string, array<string, bool>>  $reviewed
     * @param  list<MigrationRollbackPortabilityFinding>  $findings
     */
    private function appendStaleAllowlistFindings(
        array $allowlist,
        array $reviewed,
        string $category,
        array &$findings,
    ): void {
        foreach ($allowlist as $file => $references) {
            if (! is_file($this->repositoryRoot.'/'.$file)) {
                continue;
            }

            foreach (array_keys($references) as $reference) {
                if (($reviewed[$file][$reference] ?? false) === true) {
                    continue;
                }

                $findings[] = new MigrationRollbackPortabilityFinding(
                    $file,
                    0,
                    $reference,
                    "A allowlist de rollback [{$category}] está desatualizada: a ocorrência revista deixou de existir.",
                );
            }
        }
    }

    /**
     * @return list<array{line: int, reference: string}>
     */
    private function namedDropForeignCalls(string $source): array
    {
        return $this->dropForeignCallsByArgumentType($source, T_CONSTANT_ENCAPSED_STRING);
    }

    /**
     * @return list<array{line: int, reference: string}>
     */
    private function dynamicDropForeignCalls(string $source): array
    {
        return $this->dropForeignCallsByArgumentType($source, T_VARIABLE);
    }

    /**
     * @return list<array{line: int, reference: string}>
     */
    private function dropForeignCallsByArgumentType(string $source, int $argumentType): array
    {
        $tokens = token_get_all($source);
        $calls = [];
        $count = count($tokens);

        for ($index = 0; $index < $count; $index++) {
            if (! $this->isObjectOperator($tokens[$index])) {
                continue;
            }

            $methodIndex = $this->nextMeaningfulTokenIndex($tokens, $index + 1);
            if ($methodIndex === null || ! $this->isNamedToken($tokens[$methodIndex], T_STRING, 'dropForeign')) {
                continue;
            }

            $openParenthesisIndex = $this->nextMeaningfulTokenIndex($tokens, $methodIndex + 1);
            if ($openParenthesisIndex === null || $tokens[$openParenthesisIndex] !== '(') {
                continue;
            }

            $argumentIndex = $this->nextMeaningfulTokenIndex($tokens, $openParenthesisIndex + 1);
            if ($argumentIndex === null || ! is_array($tokens[$argumentIndex])) {
                continue;
            }

            if ($tokens[$argumentIndex][0] !== $argumentType) {
                continue;
            }

            if ($argumentType === T_VARIABLE) {
                $afterArgumentIndex = $this->nextMeaningfulTokenIndex(
                    $tokens,
                    $argumentIndex + 1,
                );

                if (
                    $afterArgumentIndex === null
                    || ! in_array(
                        $tokens[$afterArgumentIndex],
                        [')', ','],
                        true,
                    )
                ) {
                    continue;
                }
            }

            $reference = $argumentType === T_CONSTANT_ENCAPSED_STRING
                ? $this->decodeStringLiteral($tokens[$argumentIndex][1])
                : $tokens[$argumentIndex][1];

            $calls[] = [
                'line' => $tokens[$argumentIndex][2],
                'reference' => $reference,
            ];
        }

        return $calls;
    }

    /**
     * @return list<array{line: int, reference: string}>
     */
    private function vendorMetadataReferences(string $source): array
    {
        $references = [];

        foreach (token_get_all($source) as $token) {
            if (! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            $value = $this->decodeStringLiteral($token[1]);
            if (! str_starts_with(strtolower($value), 'information_schema.')) {
                continue;
            }

            $references[] = [
                'line' => $token[2],
                'reference' => $value,
            ];
        }

        return $references;
    }

    /**
     * @return list<array{line: int, reference: string}>
     */
    private function unscopedIndexRepairHelpers(string $source): array
    {
        $matches = [];
        $pattern = '/private\s+function\s+(ensure[A-Za-z0-9_]*Index)\s*\([^)]*\)\s*:\s*void\s*\{/s';

        if (preg_match_all($pattern, $source, $matches, PREG_OFFSET_CAPTURE) !== 1
            && ($matches[0] ?? []) === []) {
            return [];
        }

        $findings = [];

        foreach ($matches[0] as $position => $match) {
            $methodName = $matches[1][$position][0];
            $methodOffset = $match[1];
            $openingBrace = strpos($source, '{', $methodOffset);

            if ($openingBrace === false) {
                continue;
            }

            $methodBody = $this->balancedBlock($source, $openingBrace);

            if ($methodBody === null || str_contains($methodBody, 'getDriverName')) {
                continue;
            }

            $findings[] = [
                'line' => substr_count(substr($source, 0, $methodOffset), "\n") + 1,
                'reference' => $methodName,
            ];
        }

        return $findings;
    }

    /**
     * @return list<array{line: int, reference: string}>
     */
    private function indexedColumnsDroppedWithoutIndexRemoval(string $source): array
    {
        $up = $this->methodBody($source, 'up');
        $down = $this->methodBody($source, 'down');

        if ($up === null || $down === null) {
            return [];
        }

        $upTables = $this->schemaTableBlocks($up['body'], $up['line']);
        $downTables = $this->schemaTableBlocks($down['body'], $down['line']);
        $downByTable = [];
        $dynamicDownIndexes = [];

        if (str_contains($down['body'], 'dropIndex($')) {
            foreach ($this->stringLiterals($down['body']) as $literal) {
                if (str_ends_with($literal, '_idx') || str_ends_with($literal, '_index')) {
                    $dynamicDownIndexes[] = $literal;
                }
            }
        }

        foreach ($downTables as $block) {
            $table = $block['table'];
            $downByTable[$table]['columns'] = array_values(array_unique(array_merge(
                $downByTable[$table]['columns'] ?? [],
                $this->dropColumnReferences($block['body']),
            )));
            $downByTable[$table]['indexes'] = array_values(array_unique(array_merge(
                $downByTable[$table]['indexes'] ?? [],
                $this->dropIndexReferences($table, $block['body']),
            )));
        }

        $findings = [];

        foreach ($upTables as $block) {
            $table = $block['table'];
            $droppedColumns = $downByTable[$table]['columns'] ?? [];

            if ($droppedColumns === []) {
                continue;
            }

            $droppedIndexes = array_values(array_unique(array_merge(
                $downByTable[$table]['indexes'] ?? [],
                $dynamicDownIndexes,
            )));

            foreach ($this->indexDefinitions($table, $block['body'], $block['line']) as $index) {
                if (array_intersect($index['columns'], $droppedColumns) === []) {
                    continue;
                }

                if (in_array($index['name'], $droppedIndexes, true)) {
                    continue;
                }

                $findings[] = [
                    'line' => $index['line'],
                    'reference' => $index['name'],
                ];
            }
        }

        return $findings;
    }

    /**
     * @return list<array{line: int, reference: string}>
     */
    private function indexDropsAfterTableRebuild(string $source): array
    {
        $down = $this->methodBody($source, 'down');

        if ($down === null) {
            return [];
        }

        $findings = [];
        $tableWasRebuilt = [];

        foreach ($this->schemaTableBlocks($down['body'], $down['line']) as $block) {
            $table = $block['table'];
            $matches = [];

            preg_match_all(
                '/\$table\s*->\s*(dropForeign|dropConstrainedForeignId|dropColumn|dropIndex|dropUnique)\s*\(/',
                $block['body'],
                $matches,
                PREG_OFFSET_CAPTURE,
            );

            foreach ($matches[1] ?? [] as $match) {
                $operation = $match[0];
                $offset = $match[1];

                if (in_array($operation, ['dropForeign', 'dropConstrainedForeignId', 'dropColumn'], true)) {
                    $tableWasRebuilt[$table] = true;

                    continue;
                }

                if (($tableWasRebuilt[$table] ?? false) !== true) {
                    continue;
                }

                $findings[] = [
                    'line' => $block['line'] + substr_count(substr($block['body'], 0, $offset), "\n"),
                    'reference' => $table.'::'.$operation,
                ];
            }
        }

        return $findings;
    }

    /**
     * @return array{body: string, line: int}|null
     */
    private function methodBody(string $source, string $method): ?array
    {
        $pattern = '/public\s+function\s+'.preg_quote($method, '/').'\s*\([^)]*\)\s*:\s*void\s*\{/';

        if (preg_match($pattern, $source, $match, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        $offset = $match[0][1];
        $openingBrace = strpos($source, '{', $offset);

        if ($openingBrace === false) {
            return null;
        }

        $body = $this->balancedBlock($source, $openingBrace);

        if ($body === null) {
            return null;
        }

        return [
            'body' => $body,
            'line' => substr_count(substr($source, 0, $openingBrace), "\n") + 1,
        ];
    }

    /**
     * @return list<array{table: string, body: string, line: int}>
     */
    private function schemaTableBlocks(string $body, int $baseLine): array
    {
        $pattern = '/Schema::table\s*\(\s*([\'\"])([^\'\"]+)\1\s*,\s*(?:static\s+)?function\s*\([^)]*\)(?:\s*use\s*\([^)]*\))?(?:\s*:\s*void)?\s*\{/';
        $matches = [];

        if (preg_match_all($pattern, $body, $matches, PREG_OFFSET_CAPTURE) === false) {
            return [];
        }

        $blocks = [];

        foreach ($matches[0] ?? [] as $position => $match) {
            $openingBrace = strpos($body, '{', $match[1]);

            if ($openingBrace === false) {
                continue;
            }

            $block = $this->balancedBlock($body, $openingBrace);

            if ($block === null) {
                continue;
            }

            $blocks[] = [
                'table' => $matches[2][$position][0],
                'body' => $block,
                'line' => $baseLine + substr_count(substr($body, 0, $openingBrace), "\n"),
            ];
        }

        return $blocks;
    }

    /**
     * @return list<array{name: string, columns: list<string>, line: int}>
     */
    private function indexDefinitions(string $table, string $body, int $baseLine): array
    {
        $definitions = [];
        $statements = [];
        preg_match_all('/\$table\s*->[^;]+;/s', $body, $statements, PREG_OFFSET_CAPTURE);

        foreach ($statements[0] ?? [] as $statementMatch) {
            $statement = $statementMatch[0];
            $offset = $statementMatch[1];
            $line = $baseLine + substr_count(substr($body, 0, $offset), "\n");

            if (preg_match('/^\$table\s*->\s*(index|unique)\s*\(/', trim($statement), $explicit) === 1) {
                $type = strtolower($explicit[1]);
                $arguments = substr($statement, strpos($statement, '(') + 1);
                $columns = $this->stringLiteralsBeforeSecondArgument($arguments);

                if ($columns === []) {
                    continue;
                }

                $name = $this->explicitIndexName($arguments)
                    ?? $this->defaultIndexName($table, $columns, $type);

                $definitions[] = [
                    'name' => $name,
                    'columns' => $columns,
                    'line' => $line,
                ];

                continue;
            }

            if (preg_match('/^\$table\s*->\s*[A-Za-z_][A-Za-z0-9_]*\s*\(\s*([\'\"])([^\'\"]+)\1/s', trim($statement), $column) !== 1) {
                continue;
            }

            foreach (['index', 'unique'] as $type) {
                if (preg_match('/->\s*'.$type.'\s*\(\s*\)/', $statement) !== 1) {
                    continue;
                }

                $definitions[] = [
                    'name' => $this->defaultIndexName($table, [$column[2]], $type),
                    'columns' => [$column[2]],
                    'line' => $line,
                ];
            }
        }

        return $definitions;
    }

    /**
     * @return list<string>
     */
    private function dropColumnReferences(string $body): array
    {
        $columns = [];
        $matches = [];
        preg_match_all('/->\s*dropColumn\s*\((.*?)\)\s*;/s', $body, $matches);

        foreach ($matches[1] ?? [] as $arguments) {
            foreach ($this->stringLiterals($arguments) as $column) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    /**
     * @return list<string>
     */
    private function dropIndexReferences(string $table, string $body): array
    {
        $indexes = [];
        $matches = [];
        preg_match_all('/->\s*drop(Index|Unique)\s*\(\s*([\'\"])([^\'\"]+)\2\s*,?\s*\)/', $body, $matches);

        foreach ($matches[3] ?? [] as $position => $index) {
            $indexes[] = $index;
        }

        $columnDrops = [];
        preg_match_all('/->\s*drop(Index|Unique)\s*\(\s*\[([^\]]+)\]\s*,?\s*\)/s', $body, $columnDrops);

        foreach ($columnDrops[0] ?? [] as $position => $_match) {
            $type = strtolower($columnDrops[1][$position]);
            $columns = $this->stringLiterals($columnDrops[2][$position]);

            if ($columns !== []) {
                $indexes[] = $this->defaultIndexName($table, $columns, $type);
            }
        }

        if (str_contains($body, 'dropIndex($')) {
            foreach ($this->stringLiterals($body) as $literal) {
                if (str_ends_with($literal, '_idx') || str_ends_with($literal, '_index')) {
                    $indexes[] = $literal;
                }
            }
        }

        return array_values(array_unique($indexes));
    }

    /**
     * @return list<string>
     */
    private function stringLiterals(string $source): array
    {
        $matches = [];
        preg_match_all('/([\'\"])([^\'\"]+)\1/', $source, $matches);

        return array_values(array_unique($matches[2] ?? []));
    }

    /**
     * @return list<string>
     */
    private function stringLiteralsBeforeSecondArgument(string $arguments): array
    {
        $depth = 0;
        $quote = null;
        $length = strlen($arguments);
        $firstArgument = $arguments;

        for ($index = 0; $index < $length; $index++) {
            $character = $arguments[$index];

            if ($quote !== null) {
                if ($character === '\\') {
                    $index++;

                    continue;
                }

                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;

                continue;
            }

            if ($character === '[' || $character === '(') {
                $depth++;

                continue;
            }

            if ($character === ']' || $character === ')') {
                $depth--;

                continue;
            }

            if ($character === ',' && $depth === 0) {
                $firstArgument = substr($arguments, 0, $index);
                break;
            }
        }

        return $this->stringLiterals($firstArgument);
    }

    private function explicitIndexName(string $arguments): ?string
    {
        $depth = 0;
        $quote = null;
        $length = strlen($arguments);

        for ($index = 0; $index < $length; $index++) {
            $character = $arguments[$index];

            if ($quote !== null) {
                if ($character === '\\') {
                    $index++;

                    continue;
                }

                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;

                continue;
            }

            if ($character === '[' || $character === '(') {
                $depth++;

                continue;
            }

            if ($character === ']' || $character === ')') {
                $depth--;

                continue;
            }

            if ($character !== ',' || $depth !== 0) {
                continue;
            }

            $tail = substr($arguments, $index + 1);

            if (preg_match('/^\s*([\'\"])([^\'\"]+)\1/', $tail, $name) === 1) {
                return $name[2];
            }

            return null;
        }

        return null;
    }

    /**
     * @param  list<string>  $columns
     */
    private function defaultIndexName(string $table, array $columns, string $type): string
    {
        return strtolower(str_replace(['-', '.'], '_', $table.'_'.implode('_', $columns).'_'.$type));
    }

    private function balancedBlock(string $source, int $openingBrace): ?string
    {
        $depth = 0;
        $quote = null;
        $escaped = false;
        $length = strlen($source);

        for ($index = $openingBrace; $index < $length; $index++) {
            $character = $source[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;

                    continue;
                }

                if ($character === '\\') {
                    $escaped = true;

                    continue;
                }

                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;

                continue;
            }

            if ($character === '{') {
                $depth++;

                continue;
            }

            if ($character !== '}') {
                continue;
            }

            $depth--;

            if ($depth === 0) {
                return substr($source, $openingBrace, $index - $openingBrace + 1);
            }
        }

        return null;
    }

    private function isObjectOperator(array|string $token): bool
    {
        return is_array($token) && $token[0] === T_OBJECT_OPERATOR;
    }

    private function isNamedToken(array|string $token, int $type, string $value): bool
    {
        return is_array($token)
            && $token[0] === $type
            && strcasecmp($token[1], $value) === 0;
    }

    /**
     * @param  list<array{0: int, 1: string, 2: int}|string>  $tokens
     */
    private function nextMeaningfulTokenIndex(array $tokens, int $start): ?int
    {
        $count = count($tokens);

        for ($index = $start; $index < $count; $index++) {
            $token = $tokens[$index];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $index;
        }

        return null;
    }

    private function decodeStringLiteral(string $literal): string
    {
        $quote = $literal[0] ?? '';
        $value = substr($literal, 1, -1);

        if ($quote === "'") {
            return str_replace(['\\\\', "\\'"], ['\\', "'"], $value);
        }

        return stripcslashes($value);
    }

    private function relativePath(string $path): string
    {
        $root = rtrim(str_replace('\\', '/', realpath($this->repositoryRoot) ?: $this->repositoryRoot), '/');
        $normalizedPath = str_replace('\\', '/', realpath($path) ?: $path);

        return ltrim(substr($normalizedPath, strlen($root)), '/');
    }
}

function runMigrationRollbackPortabilityAudit(array $arguments): int
{
    $repositoryRoot = $arguments[1] ?? dirname(__DIR__, 2);
    $result = (new MigrationRollbackPortabilityAuditor($repositoryRoot))->audit();

    if ($result->passed()) {
        printf(
            "MIGRATION_ROLLBACK_PORTABILITY=PASS\nMIGRATIONS_SCANNED=%d\n",
            count($result->files),
        );

        return 0;
    }

    fwrite(STDERR, "MIGRATION_ROLLBACK_PORTABILITY=FAIL\n");

    foreach ($result->findings as $finding) {
        $location = $finding->line > 0
            ? "{$finding->file}:{$finding->line}"
            : $finding->file;

        fwrite(STDERR, "- {$location} [{$finding->reference}] {$finding->message}\n");
    }

    return 1;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(runMigrationRollbackPortabilityAudit($argv));
}
