<?php

namespace App\Data\Program53;

use App\Enums\ApplicationResultExportFormat;
use InvalidArgumentException;

final readonly class Program53BenchmarkConfiguration
{
    /**
     * @param  list<ApplicationResultExportFormat>  $formats
     */
    public function __construct(
        public int $applications,
        public int $analysts,
        public int $municipalities,
        public int $contests,
        public int $seed,
        public string $scenario,
        public array $formats,
        public int $queueWorkers,
        public ?string $output,
        public bool $cleanup,
        public bool $assert,
    ) {
        $maximum = (int) config('program53.benchmark.max_applications', 50_000);
        if ($applications < 1 || $applications > $maximum) {
            throw new InvalidArgumentException(
                "O benchmark exige entre 1 e {$maximum} candidaturas.",
            );
        }
        if ($analysts < 1 || $analysts > 64) {
            throw new InvalidArgumentException('O número de analistas deve estar entre 1 e 64.');
        }
        if ($municipalities < 1 || $municipalities > 16) {
            throw new InvalidArgumentException('O número de Municípios deve estar entre 1 e 16.');
        }
        if ($contests < $municipalities || $contests > 64 || $contests > $applications) {
            throw new InvalidArgumentException(
                'O número de concursos deve cobrir os Municípios e não exceder 64.',
            );
        }
        if ($queueWorkers < 1 || $queueWorkers > 32) {
            throw new InvalidArgumentException('O benchmark exige entre 1 e 32 workers assíncronos.');
        }
        if ($formats === []) {
            throw new InvalidArgumentException('Indique pelo menos um formato de exportação.');
        }
        if (preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $scenario) !== 1) {
            throw new InvalidArgumentException('O identificador do cenário não é seguro.');
        }
        if ($output !== null && (
            $output === ''
            || ! str_starts_with($output, 'storage/qa/')
            || str_contains($output, '..')
            || preg_match('/^[A-Za-z0-9_\/.\-]+$/', $output) !== 1
        )) {
            throw new InvalidArgumentException(
                'O output deve ser um path relativo seguro dentro de storage/qa.',
            );
        }
    }

    /** @return array<string, int|string|bool|list<string>|null> */
    public function toArray(): array
    {
        return [
            'applications' => $this->applications,
            'analysts' => $this->analysts,
            'municipalities' => $this->municipalities,
            'contests' => $this->contests,
            'seed' => $this->seed,
            'scenario' => $this->scenario,
            'formats' => array_map(
                static fn (ApplicationResultExportFormat $format): string => $format->value,
                $this->formats,
            ),
            'queue_workers' => $this->queueWorkers,
            'output' => $this->output,
            'cleanup' => $this->cleanup,
            'assert' => $this->assert,
        ];
    }
}
