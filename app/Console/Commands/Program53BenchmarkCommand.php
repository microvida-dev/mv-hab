<?php

namespace App\Console\Commands;

use App\Data\Program53\Program53BenchmarkConfiguration;
use App\Enums\ApplicationResultExportFormat;
use App\Services\Program53\Benchmark\Program53BenchmarkEnvironment;
use App\Services\Program53\Benchmark\Program53BenchmarkReportWriter;
use App\Services\Program53\Benchmark\Program53BenchmarkRunner;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

final class Program53BenchmarkCommand extends Command
{
    protected $signature = 'program53:benchmark
        {--applications=1000 : Número de candidaturas sintéticas}
        {--analysts=4 : Analistas lógicos}
        {--municipalities=2 : Municípios sintéticos}
        {--contests=2 : Concursos sintéticos}
        {--seed=53001000 : Seed determinístico}
        {--scenario=smoke : Nome seguro do cenário}
        {--formats=csv,json,xml,xlsx : Formatos separados por vírgula}
        {--queue-workers=1 : Workers assíncronos lógicos}
        {--output= : Base relativa do relatório em storage/qa}
        {--cleanup : Eliminar base e storage temporários}
        {--assert : Falhar se um guardrail não for cumprido}';

    protected $description = 'Executa benchmark sintético e isolado do Programa 53.';

    public function handle(
        Program53BenchmarkRunner $runner,
        Program53BenchmarkReportWriter $reports,
        Program53BenchmarkEnvironment $environment,
    ): int {
        try {
            $configuration = $this->configuration();
            $runId = $environment->runId($configuration);
            $this->info(sprintf(
                'Programa 53: cenário %s com %d candidaturas sintéticas.',
                $configuration->scenario,
                $configuration->applications,
            ));
            $result = $runner->run($configuration);
            $paths = $reports->write($configuration, $result);
            if ($configuration->cleanup) {
                $environment->cleanup($runId);
            }

            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['resultado', (string) $result['result']],
                    ['candidaturas', (string) $configuration->applications],
                    ['queries', (string) $result['queries']],
                    ['peak memory', (string) $result['peak_memory_bytes']],
                    ['JSON', $paths['json']],
                    ['Markdown', $paths['markdown']],
                ],
            );

            return $configuration->assert && $result['result'] !== 'pass'
                ? self::FAILURE
                : self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        } catch (Throwable $exception) {
            $this->error('Benchmark abortado de forma segura: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function configuration(): Program53BenchmarkConfiguration
    {
        $formats = array_values(array_unique(array_filter(array_map(
            static fn (string $format): string => trim(strtolower($format)),
            explode(',', (string) $this->option('formats')),
        ))));
        $known = array_column(ApplicationResultExportFormat::cases(), 'value');
        foreach ($formats as $format) {
            if (! in_array($format, $known, true)) {
                throw new InvalidArgumentException("Formato não suportado: {$format}.");
            }
        }

        $output = $this->option('output');

        return new Program53BenchmarkConfiguration(
            applications: (int) $this->option('applications'),
            analysts: (int) $this->option('analysts'),
            municipalities: (int) $this->option('municipalities'),
            contests: (int) $this->option('contests'),
            seed: (int) $this->option('seed'),
            scenario: (string) $this->option('scenario'),
            formats: array_map(
                static fn (string $format): ApplicationResultExportFormat => ApplicationResultExportFormat::from($format),
                $formats,
            ),
            queueWorkers: (int) $this->option('queue-workers'),
            output: is_string($output) && $output !== '' ? $output : null,
            cleanup: (bool) $this->option('cleanup'),
            assert: (bool) $this->option('assert'),
        );
    }
}
