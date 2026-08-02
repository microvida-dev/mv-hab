<?php

namespace App\Console\Commands;

use App\Services\Program53\Operations\Program53OperationalHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;

final class Program53OperationalCheckCommand extends Command
{
    protected $signature = 'program53:operational-check
        {--format=table : Formato de saída: table, json ou markdown}
        {--output= : Path relativo opcional dentro de storage/qa}
        {--fail-on-warning : Terminar com erro perante warnings}
        {--fail-on-critical : Terminar com erro perante findings críticos}';

    protected $description = 'Inspeciona read-only o estado operacional do Programa 53.';

    public function handle(Program53OperationalHealthService $health): int
    {
        $format = strtolower(trim((string) $this->option('format')));
        if (! in_array($format, ['table', 'json', 'markdown'], true)) {
            $this->components->error('A opção --format deve ser table, json ou markdown.');

            return self::INVALID;
        }

        try {
            $output = $this->outputPath();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::INVALID;
        }

        $result = $health->inspect();
        $serialized = match ($format) {
            'json' => $this->json($result),
            'markdown' => $this->markdown($result),
            default => null,
        };

        if ($output !== null) {
            File::ensureDirectoryExists(dirname($output));
            File::put($output, $serialized ?? $this->markdown($result));
        } elseif ($serialized !== null) {
            $this->line($serialized);
        } else {
            $this->table(
                ['Código', 'Severidade', 'Descrição'],
                array_map(
                    static fn (array $finding): array => [
                        $finding['code'],
                        strtoupper($finding['severity']),
                        $finding['message'],
                    ],
                    $result['findings'],
                ),
            );
        }

        if ((bool) $this->option('fail-on-critical') && $result['summary']['critical'] > 0) {
            return self::FAILURE;
        }
        if ((bool) $this->option('fail-on-warning') && $result['summary']['warning'] > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $result
     *
     * @throws JsonException
     */
    private function json(array $result): string
    {
        return json_encode(
            $result,
            JSON_THROW_ON_ERROR
                | JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE,
        ).PHP_EOL;
    }

    /** @param array<string, mixed> $result */
    private function markdown(array $result): string
    {
        /** @var array{total: int, info: int, warning: int, critical: int} $summary */
        $summary = $result['summary'];
        /** @var list<array{code: string, severity: string, message: string}> $findings */
        $findings = $result['findings'];
        $lines = [
            '# Health operacional do Programa 53',
            '',
            '- Verificações: '.$summary['total'],
            '- Informativas: '.$summary['info'],
            '- Avisos: '.$summary['warning'],
            '- Críticas: '.$summary['critical'],
            '',
            '| Código | Severidade | Descrição |',
            '|---|---|---|',
        ];
        foreach ($findings as $finding) {
            $lines[] = sprintf(
                '| `%s` | %s | %s |',
                str_replace('|', '\\|', $finding['code']),
                strtoupper($finding['severity']),
                str_replace('|', '\\|', $finding['message']),
            );
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private function outputPath(): ?string
    {
        $output = $this->option('output');
        if (! is_string($output) || trim($output) === '') {
            return null;
        }

        $output = trim($output);
        if (
            ! str_starts_with($output, 'storage/qa/')
            || str_contains($output, '..')
            || preg_match('/^[A-Za-z0-9_\/.\-]+$/', $output) !== 1
        ) {
            throw new InvalidArgumentException(
                'O output deve ser um path relativo seguro dentro de storage/qa.',
            );
        }

        return base_path($output);
    }
}
