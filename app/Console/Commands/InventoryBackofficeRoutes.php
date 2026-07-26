<?php

namespace App\Console\Commands;

use App\Enums\BackofficeRouteBoundedContext;
use App\Enums\RouteInventoryRisk;
use App\Services\Access\BackofficeRouteInventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;
use LogicException;

/**
 * @phpstan-import-type InventoryRouteRow from BackofficeRouteInventoryService
 * @phpstan-import-type InventorySummary from BackofficeRouteInventoryService
 */
class InventoryBackofficeRoutes extends Command
{
    protected $signature = 'access:inventory-backoffice-routes
        {--format=table : Output format: table, json, csv or markdown}
        {--output= : Optional output file path}
        {--only-fixed-role : Include only backoffice routes with active role:* middleware}
        {--bounded-context= : Filter by bounded context}
        {--risk= : Filter by risk: critical, high, medium or low}
        {--missing-permission : Include only routes without adequate permission middleware}
        {--missing-policy : Include only model-bound routes without a detected Policy}
        {--missing-scope : Include only routes with missing municipal record scope}
        {--mutation-without-audit : Include only required mutations without detected audit}';

    protected $description = 'Build a deterministic, read-only permission-first inventory of backoffice routes.';

    public function __construct(
        private readonly BackofficeRouteInventoryService $inventory,
    ) {
        parent::__construct();
    }

    /**
     * @throws JsonException
     */
    public function handle(): int
    {
        $format = strtolower((string) $this->option('format'));

        if (! in_array($format, ['table', 'json', 'csv', 'markdown'], true)) {
            throw new InvalidArgumentException(
                'The --format option must be table, json, csv or markdown.'
            );
        }

        $rows = $this->applyFilters($this->inventory->inventory());
        $summary = $this->inventory->summary($rows);
        $serialized = match ($format) {
            'json' => $this->asJson($rows, $summary),
            'csv' => $this->asCsv($rows),
            'markdown' => $this->asMarkdown($rows, $summary),
            default => null,
        };

        if ($output = $this->normalizedOutputPath()) {
            $content = $format === 'table'
                ? $this->asMarkdown($rows, $summary)
                : $serialized;

            if (! is_string($content)) {
                throw new LogicException('O inventário de rotas não foi serializado.');
            }

            File::ensureDirectoryExists(dirname($output));
            File::put($output, $content);
            $this->info("Backoffice route inventory written to: {$output}");

            return self::SUCCESS;
        }

        if (is_string($serialized)) {
            $this->line($serialized);

            return self::SUCCESS;
        }

        $this->renderSummary($summary);
        $this->renderTable($rows);

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, covariant InventoryRouteRow>  $rows
     * @return Collection<int, covariant InventoryRouteRow>
     */
    private function applyFilters(Collection $rows): Collection
    {
        if ((bool) $this->option('only-fixed-role')) {
            $rows = $rows->filter(
                fn (array $row): bool => is_string($row['role_middleware_active'])
            );
        }

        if ($context = $this->validatedBoundedContext()) {
            $rows = $rows->where('bounded_context', $context->value);
        }

        if ($risk = $this->validatedRisk()) {
            $rows = $rows->where('risk', $risk->value);
        }

        if ((bool) $this->option('missing-permission')) {
            $rows = $rows->where('permission_semantically_adequate', false);
        }

        if ((bool) $this->option('missing-policy')) {
            $rows = $rows->filter(
                fn (array $row): bool => is_string($row['record_model'])
                    && $row['policy_class'] === null
            );
        }

        if ((bool) $this->option('missing-scope')) {
            $rows = $rows->where('municipal_record_scope', 'missing');
        }

        if ((bool) $this->option('mutation-without-audit')) {
            $rows = $rows
                ->where('operation_type', 'mutation')
                ->where('audit_requirement', 'required')
                ->where('audit_implementation', 'missing');
        }

        return $rows->values();
    }

    private function validatedBoundedContext(): ?BackofficeRouteBoundedContext
    {
        $value = $this->option('bounded-context');

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $context = BackofficeRouteBoundedContext::tryFrom(trim($value));

        if (! $context instanceof BackofficeRouteBoundedContext) {
            throw new InvalidArgumentException(
                'Unknown bounded context. Use one of: '.implode(
                    ', ',
                    array_column(BackofficeRouteBoundedContext::cases(), 'value'),
                )
            );
        }

        return $context;
    }

    private function validatedRisk(): ?RouteInventoryRisk
    {
        $value = $this->option('risk');

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $risk = RouteInventoryRisk::tryFrom(trim($value));

        if (! $risk instanceof RouteInventoryRisk) {
            throw new InvalidArgumentException(
                'Unknown risk. Use one of: '.implode(
                    ', ',
                    array_column(RouteInventoryRisk::cases(), 'value'),
                )
            );
        }

        return $risk;
    }

    /**
     * @param  Collection<int, covariant InventoryRouteRow>  $rows
     * @param  InventorySummary  $summary
     *
     * @throws JsonException
     */
    private function asJson(Collection $rows, array $summary): string
    {
        return json_encode(
            [
                'schema_version' => 1,
                'summary' => $summary,
                'routes' => $rows->all(),
            ],
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ).PHP_EOL;
    }

    /**
     * @param  Collection<int, covariant InventoryRouteRow>  $rows
     */
    private function asCsv(Collection $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new InvalidArgumentException('Unable to create temporary CSV stream.');
        }

        $headers = $rows->isNotEmpty()
            ? array_keys($rows->first())
            : $this->csvHeaders();

        fputcsv($stream, $headers);

        foreach ($rows as $row) {
            fputcsv($stream, array_map(
                fn (string $header): string => $this->csvValue($row[$header] ?? null),
                $headers,
            ));
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if ($csv === false) {
            throw new InvalidArgumentException('Unable to read generated CSV.');
        }

        return $csv;
    }

    /**
     * @param  Collection<int, covariant InventoryRouteRow>  $rows
     * @param  InventorySummary  $summary
     */
    private function asMarkdown(Collection $rows, array $summary): string
    {
        $lines = [
            '# Inventário permission-first de rotas backoffice',
            '',
            'Output determinístico do comando `access:inventory-backoffice-routes`.',
            '',
            '## Resumo',
            '',
            '| Métrica | Valor |',
            '| --- | ---: |',
        ];

        foreach ($summary as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $lines[] = '| `'.$key.'` | '.$this->markdownValue($value).' |';
        }

        $lines = [
            ...$lines,
            '',
            '## Distribuição por sprint',
            '',
            '| Sprint | Rotas |',
            '| --- | ---: |',
        ];

        $bySprint = $summary['by_target_sprint'];

        foreach ($bySprint as $sprint => $count) {
            $lines[] = "| {$sprint} | {$count} |";
        }

        $lines = [
            ...$lines,
            '',
            '## Rotas',
            '',
            '| Rota | Métodos | Contexto | Risco | Permission recomendada | Policy | Scope | Sprint | Confiança |',
            '| --- | --- | --- | --- | --- | --- | --- | --- | --- |',
        ];

        foreach ($rows as $row) {
            $lines[] = implode(' | ', [
                '| `'.$this->escapeMarkdown((string) ($row['route_name'] ?? '—')).'`',
                $this->escapeMarkdown(implode(',', $this->stringArray($row['http_methods']))),
                $this->escapeMarkdown((string) $row['bounded_context']),
                $this->escapeMarkdown((string) $row['risk']),
                '`'.$this->escapeMarkdown((string) ($row['permission_recommendation'] ?? 'em falta')).'`',
                '`'.$this->escapeMarkdown((string) ($row['policy_class'] ?? 'em falta')).'`',
                $this->escapeMarkdown((string) $row['municipal_record_scope']),
                $this->escapeMarkdown((string) $row['target_sprint']),
                $this->escapeMarkdown((string) $row['confidence']).' |',
            ]);
        }

        $lines[] = '';
        $lines[] = 'Os campos completos de cada rota estão nos artefactos JSON e CSV.';
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param  InventorySummary  $summary
     */
    private function renderSummary(array $summary): void
    {
        foreach ($summary as $key => $value) {
            if (! is_array($value)) {
                $this->components->twoColumnDetail(
                    str_replace('_', ' ', ucfirst($key)),
                    $this->csvValue($value),
                );
            }
        }
    }

    /**
     * @param  Collection<int, covariant InventoryRouteRow>  $rows
     */
    private function renderTable(Collection $rows): void
    {
        $this->newLine();
        $this->table(
            ['Route', 'Methods', 'Context', 'Risk', 'Permission', 'Scope', 'Sprint'],
            $rows->map(fn (array $row): array => [
                $row['route_name'] ?? '—',
                implode(',', $this->stringArray($row['http_methods'])),
                $row['bounded_context'],
                $row['risk'],
                $row['permission_recommendation'] ?? '—',
                $row['municipal_record_scope'],
                $row['target_sprint'],
            ])->all(),
        );
    }

    private function normalizedOutputPath(): ?string
    {
        $output = $this->option('output');

        if (! is_string($output) || trim($output) === '') {
            return null;
        }

        return str_starts_with($output, DIRECTORY_SEPARATOR)
            ? $output
            : base_path($output);
    }

    /**
     * @return list<string>
     */
    private function csvHeaders(): array
    {
        return [
            'route_name',
            'uri',
            'http_methods',
            'controller_class',
            'controller_method',
            'middleware_resolved',
            'active_backoffice_present',
            'mfa_backoffice_present',
            'log_backoffice_present',
            'role_middleware_active',
            'role_middleware_excluded',
            'permission_middleware',
            'permission_catalog_exists',
            'semantic_permission_available',
            'permission_recommendation',
            'permission_semantically_adequate',
            'policy_class',
            'policy_ability',
            'policy_ability_source',
            'form_request',
            'form_request_authorize',
            'feature_entitlement',
            'feature_required',
            'feature_key',
            'municipality_source',
            'municipal_record_scope',
            'fail_closed_without_municipality',
            'platform_route',
            'municipal_route',
            'mixed_context_route',
            'record_model',
            'operation_type',
            'mfa_sensitive',
            'audit_requirement',
            'audit_implementation',
            'private_data',
            'bounded_context',
            'bounded_context_label',
            'target_sprint',
            'risk',
            'confidence',
            'test_coverage',
            'test_sources',
            'source',
            'migration_recommendation',
        ];
    }

    private function csvValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            return implode('|', array_map(
                fn (mixed $item): string => is_scalar($item) ? (string) $item : '',
                $value,
            ));
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private function markdownValue(mixed $value): string
    {
        return $this->escapeMarkdown($this->csvValue($value));
    }

    private function escapeMarkdown(string $value): string
    {
        return str_replace('|', '\\|', $value);
    }

    /**
     * @return list<string>
     */
    private function stringArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            fn (mixed $item): bool => is_string($item),
        ));
    }
}
