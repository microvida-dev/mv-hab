<?php

namespace App\Services\Program53\Benchmark;

use App\Data\Program53\Program53BenchmarkConfiguration;
use JsonException;
use RuntimeException;

final class Program53BenchmarkReportWriter
{
    /**
     * @param  array<string, mixed>  $result
     * @return array{json: string, markdown: string}
     *
     * @throws JsonException
     */
    public function write(
        Program53BenchmarkConfiguration $configuration,
        array $result,
    ): array {
        $base = $configuration->output
            ?? sprintf(
                '%s/program53-benchmark-%s-%d',
                rtrim((string) config('program53.benchmark.output_directory', 'storage/qa'), '/'),
                $configuration->scenario,
                $configuration->applications,
            );
        $base = preg_replace('/\.(?:json|md)$/', '', $base) ?? $base;
        $this->assertPath($base);
        $directory = base_path(dirname($base));
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Não foi possível criar a diretoria dos relatórios de benchmark.');
        }
        $jsonPath = base_path($base.'.json');
        $markdownPath = base_path($base.'.md');
        $json = json_encode(
            $result,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        )."\n";
        if (file_put_contents($jsonPath, $json, LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível escrever o relatório JSON do benchmark.');
        }
        if (file_put_contents($markdownPath, $this->markdown($result), LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível escrever o relatório Markdown do benchmark.');
        }

        return [
            'json' => $base.'.json',
            'markdown' => $base.'.md',
        ];
    }

    /** @param array<string, mixed> $result */
    private function markdown(array $result): string
    {
        $dataset = is_array($result['dataset'] ?? null) ? $result['dataset'] : [];
        $runtime = is_array($result['runtime'] ?? null) ? $result['runtime'] : [];
        $counts = is_array($result['counts'] ?? null) ? $result['counts'] : [];
        $durations = is_array($result['durations_seconds'] ?? null)
            ? $result['durations_seconds']
            : [];
        $throughput = is_array($result['throughput'] ?? null) ? $result['throughput'] : [];
        $integrity = is_array($result['integrity'] ?? null) ? $result['integrity'] : [];
        $lines = [
            '# Programa 53 - Benchmark '.($dataset['scenario'] ?? 'synthetic'),
            '',
            '- Resultado: `'.($result['result'] ?? 'unknown').'`',
            '- Commit: `'.($result['commit'] ?? 'unknown').'`',
            '- Candidaturas: '.($dataset['applications'] ?? 0),
            '- Municipios: '.($dataset['municipalities'] ?? 0),
            '- Concursos: '.($dataset['contests'] ?? 0),
            '- Analistas: '.($dataset['analysts'] ?? 0),
            '- Queue: `'.($runtime['queue_driver'] ?? 'unknown').'`',
            '- Peak memory: '.($result['peak_memory_bytes'] ?? 0).' bytes',
            '- Queries: '.($result['queries'] ?? 0),
            '- Throughput: '.($throughput['applications_per_second'] ?? 0).' candidaturas/s',
            '',
            '## Contagens',
            '',
            '| Entidade | Total |',
            '|---|---:|',
        ];
        foreach ($counts as $name => $count) {
            $lines[] = '| '.$name.' | '.$count.' |';
        }
        $lines[] = '';
        $lines[] = '## Duracoes';
        $lines[] = '';
        $lines[] = '| Fase | Segundos |';
        $lines[] = '|---|---:|';
        foreach ($durations as $phase => $duration) {
            $lines[] = '| '.$phase.' | '.$duration.' |';
        }
        $lines[] = '';
        $lines[] = '## Integridade';
        $lines[] = '';
        foreach ($integrity as $gate => $passed) {
            $lines[] = '- '.($passed ? '[x]' : '[ ]').' `'.$gate.'`';
        }
        $lines[] = '';
        $lines[] = '> Dados exclusivamente sinteticos; guardrails tecnicos, nao SLA.';

        return implode("\n", $lines)."\n";
    }

    private function assertPath(string $base): void
    {
        if (
            ! str_starts_with($base, 'storage/qa/')
            || str_contains($base, '..')
            || preg_match('/^[A-Za-z0-9_\/.\-]+$/', $base) !== 1
        ) {
            throw new RuntimeException('O relatório deve ficar em storage/qa.');
        }
    }
}
