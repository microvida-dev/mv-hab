<?php

namespace App\Console\Commands;

use App\Services\Access\Program53AccessAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;

/** @phpstan-import-type Program53AuditResult from Program53AccessAuditService */
final class AuditProgram53Access extends Command
{
    protected $signature = 'access:audit-program-53
        {--format=table : Formato de saída: table, json ou markdown}
        {--output= : Caminho opcional para guardar o resultado}
        {--fail-on-drift : Terminar com erro quando existir divergência}';

    protected $description =
        'Audita, sem alterações, a matriz de acesso do Programa 53.';

    public function handle(Program53AccessAuditService $audit): int
    {
        $format = strtolower(trim((string) $this->option('format')));

        if (! in_array($format, ['table', 'json', 'markdown'], true)) {
            $this->components->error(
                'A opção --format deve ser table, json ou markdown.',
            );

            return self::INVALID;
        }

        $result = $audit->audit();
        $serialized = match ($format) {
            'json' => $this->json($result),
            'markdown' => $this->markdown($result),
            default => null,
        };
        $output = $this->outputPath();

        if ($output !== null) {
            $content = $serialized ?? $this->markdown($result);
            File::ensureDirectoryExists(dirname($output));
            File::put($output, $content);
            $this->components->info(
                'Auditoria do Programa 53 guardada em '.$output.'.',
            );
        } elseif ($serialized !== null) {
            $this->line($serialized);
        } else {
            $this->renderTable($result);
        }

        if ((bool) $this->option('fail-on-drift')
            && $result['summary']['drift']) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  Program53AuditResult  $result
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

    /** @param Program53AuditResult $result */
    private function markdown(array $result): string
    {
        $lines = [
            '# Auditoria de acesso do Programa 53',
            '',
            '- Template: `'.$result['template_key'].'`',
            '- Verificações: '.$result['summary']['total'],
            '- Aprovadas: '.$result['summary']['passed'],
            '- Divergências: '.$result['summary']['failed'],
            '',
            '| Código | Estado | Descrição |',
            '|---|---|---|',
        ];

        foreach ($result['checks'] as $check) {
            $lines[] = sprintf(
                '| `%s` | %s | %s |',
                str_replace('|', '\\|', $check['code']),
                strtoupper($check['status']),
                str_replace('|', '\\|', $check['message']),
            );
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /** @param Program53AuditResult $result */
    private function renderTable(array $result): void
    {
        $this->components->twoColumnDetail(
            'Verificações',
            (string) $result['summary']['total'],
        );
        $this->components->twoColumnDetail(
            'Aprovadas',
            (string) $result['summary']['passed'],
        );
        $this->components->twoColumnDetail(
            'Divergências',
            (string) $result['summary']['failed'],
        );
        $this->newLine();
        $this->table(
            ['Código', 'Estado', 'Descrição'],
            array_map(
                static fn (array $check): array => [
                    $check['code'],
                    strtoupper($check['status']),
                    $check['message'],
                ],
                $result['checks'],
            ),
        );
    }

    private function outputPath(): ?string
    {
        $output = $this->option('output');

        if (! is_string($output) || trim($output) === '') {
            return null;
        }

        $output = trim($output);

        return str_starts_with($output, DIRECTORY_SEPARATOR)
            ? $output
            : base_path($output);
    }
}
