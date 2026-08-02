<?php

namespace App\Services\Program53\Benchmark;

use RuntimeException;

final class Program53BenchmarkMetrics
{
    /** @var array<string, float> */
    private array $started = [];

    /** @var array<string, float> */
    private array $durations = [];

    private int $queries = 0;

    private int $retries = 0;

    private int $deadlocks = 0;

    public function start(string $phase): void
    {
        $this->started[$phase] = hrtime(true) / 1_000_000_000;
    }

    public function stop(string $phase): float
    {
        $started = $this->started[$phase] ?? null;
        if (! is_float($started)) {
            throw new RuntimeException("A fase {$phase} não foi iniciada.");
        }

        $duration = (hrtime(true) / 1_000_000_000) - $started;
        $this->durations[$phase] = round($duration, 6);
        unset($this->started[$phase]);

        return $duration;
    }

    public function query(int $count = 1): void
    {
        $this->queries += max(0, $count);
    }

    public function retry(bool $deadlock = false): void
    {
        $this->retries++;
        if ($deadlock) {
            $this->deadlocks++;
        }
    }

    /** @return array<string, float> */
    public function durations(): array
    {
        return $this->durations;
    }

    public function queries(): int
    {
        return $this->queries;
    }

    public function retries(): int
    {
        return $this->retries;
    }

    public function deadlocks(): int
    {
        return $this->deadlocks;
    }
}
