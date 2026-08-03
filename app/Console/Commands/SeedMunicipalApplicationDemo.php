<?php

namespace App\Console\Commands;

use App\Services\Demo\MunicipalApplicationDemoSummaryService;
use App\Support\Demo\MunicipalApplicationDemoContext;
use Database\Seeders\Demo\MunicipalApplicationDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use JsonException;
use LogicException;
use Throwable;

final class SeedMunicipalApplicationDemo extends Command
{
    public const LOCK_NAME =
        'mvhab:demo:municipal-application:seed';

    protected $signature =
        'mvhab:demo:municipal-application
        {--force : Execute without interactive confirmation}
        {--verify-only : Validate the existing scenario without seeding}
        {--format=table : Output format: table or json}';

    protected $description =
        'Seed and verify the isolated municipal application demo.';

    public function handle(
        MunicipalApplicationDemoContext $context,
        MunicipalApplicationDemoSummaryService $summaryService,
    ): int {
        $format = strtolower(
            trim((string) $this->option('format')),
        );

        if (! in_array($format, ['table', 'json'], true)) {
            $this->components->error(
                'A opção --format deve ser table ou json.',
            );

            return self::INVALID;
        }

        try {
            $context->assertSeederAllowed();

            $verifyOnly = (bool) $this->option('verify-only');

            if (
                ! $verifyOnly
                && ! (bool) $this->option('force')
                && ! $this->confirm(
                    'Criar ou atualizar o cenário municipal '
                    .'fictício MV-HAB?',
                    false,
                )
            ) {
                $this->components->warn(
                    'Operação cancelada sem alterações.',
                );

                return self::SUCCESS;
            }

            $lock = Cache::lock(self::LOCK_NAME, 600);

            if (! $lock->get()) {
                $this->components->error(
                    'Já existe uma execução do cenário municipal '
                    .'demo em curso.',
                );

                return self::FAILURE;
            }

            return $this->runWithLock(
                lock: $lock,
                verifyOnly: $verifyOnly,
                format: $format,
                summaryService: $summaryService,
            );
        } catch (Throwable $exception) {
            $this->components->error(
                'Falha no cenário municipal demo: '
                .$exception->getMessage(),
            );

            return self::FAILURE;
        }
    }

    private function runWithLock(
        Lock $lock,
        bool $verifyOnly,
        string $format,
        MunicipalApplicationDemoSummaryService $summaryService,
    ): int {
        try {
            if (! $verifyOnly) {
                $exitCode = $this->callSilent(
                    'db:seed',
                    [
                        '--class' => MunicipalApplicationDemoSeeder::class,
                        '--force' => true,
                    ],
                );

                if ($exitCode !== self::SUCCESS) {
                    throw new LogicException(
                        'O orquestrador municipal terminou '
                        .'com código '.$exitCode.'.',
                    );
                }
            }

            $summary = $summaryService->verify();

            $this->renderSummary(
                summary: $summary,
                format: $format,
                verifyOnly: $verifyOnly,
            );

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function renderSummary(
        array $summary,
        string $format,
        bool $verifyOnly,
    ): void {
        if ($format === 'json') {
            $this->line($this->asJson($summary));

            return;
        }

        $this->components->info(
            $verifyOnly
                ? 'Cenário municipal demo verificado.'
                : 'Cenário municipal demo criado e verificado.',
        );

        $municipality = $this->arraySection(
            $summary,
            'municipality',
        );
        $contest = $this->arraySection($summary, 'contest');
        $application = $this->arraySection(
            $summary,
            'application',
        );
        $counts = $this->intSection($summary, 'counts');

        $this->components->twoColumnDetail(
            'Município',
            (string) ($municipality['name'] ?? '—'),
        );
        $this->components->twoColumnDetail(
            'Código municipal',
            (string) ($municipality['code'] ?? '—'),
        );
        $this->components->twoColumnDetail(
            'Concurso',
            (string) ($contest['code'] ?? '—'),
        );
        $this->components->twoColumnDetail(
            'Candidatura',
            (string) ($application['number'] ?? '—'),
        );
        $this->components->twoColumnDetail(
            'Processo',
            (string) ($application['process_number'] ?? '—'),
        );

        $this->newLine();
        $this->table(
            ['Indicador', 'Total'],
            collect($counts)
                ->map(
                    static fn (
                        int $value,
                        string $key,
                    ): array => [
                        str_replace('_', ' ', $key),
                        (string) $value,
                    ],
                )
                ->values()
                ->all(),
        );

        $this->newLine();
        $this->components->warn(
            (string) (
                $summary['demo_notice']
                ?? 'Dados fictícios e sem efeitos administrativos.'
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function asJson(array $summary): string
    {
        try {
            return json_encode(
                $summary,
                JSON_THROW_ON_ERROR
                    | JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                'Não foi possível serializar o resumo demo.',
                previous: $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    private function arraySection(
        array $summary,
        string $key,
    ): array {
        $section = $summary[$key] ?? [];

        return is_array($section) ? $section : [];
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, int>
     */
    private function intSection(
        array $summary,
        string $key,
    ): array {
        $section = $this->arraySection($summary, $key);
        $values = [];

        foreach ($section as $name => $value) {
            if (is_int($value)) {
                $values[$name] = $value;
            }
        }

        return $values;
    }
}
