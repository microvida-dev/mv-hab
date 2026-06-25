<?php

namespace App\Services\Operations;

use Illuminate\Support\Facades\Schema;

class QueueHealthService
{
    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    public function checks(): array
    {
        $connection = (string) config('queue.default', 'sync');
        $environment = (string) config('app.env', 'production');

        $checks = [
            $this->queueConnectionCheck($connection, $environment),
            $this->failedJobsConfigurationCheck(),
            $this->privateStorageCheck(),
        ];

        if ($connection === 'database') {
            $checks = [
                ...$checks,
                $this->tableCheck('jobs'),
                $this->tableCheck('failed_jobs'),
                $this->tableCheck('job_batches'),
            ];
        }

        return $checks;
    }

    public function hasBlockingFailures(): bool
    {
        foreach ($this->checks() as $check) {
            if ($check['status'] === 'fail') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function queueConnectionCheck(string $connection, string $environment): array
    {
        if (in_array($connection, ['database', 'redis'], true)) {
            return $this->check('queue_connection', 'pass', "Queue connection {$connection} suportada para staging/piloto.");
        }

        if ($connection === 'sync' && in_array($environment, ['local', 'testing'], true)) {
            return $this->check('queue_connection', 'warn', 'QUEUE_CONNECTION=sync apenas aceitavel em local/testes.');
        }

        return $this->check('queue_connection', 'fail', "Queue connection {$connection} nao e aceitavel para producao/staging.");
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function failedJobsConfigurationCheck(): array
    {
        $failed = config('queue.failed');

        if (is_array($failed) && ($failed['driver'] ?? null) === 'database-uuids') {
            return $this->check('failed_jobs_configuration', 'pass', 'Failed jobs configurados com database-uuids.');
        }

        return $this->check('failed_jobs_configuration', 'fail', 'Failed jobs devem estar configurados para persistencia auditavel.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function privateStorageCheck(): array
    {
        $path = storage_path('app/private');

        if (is_dir($path) && is_writable($path)) {
            return $this->check('private_storage', 'pass', 'Storage privado existe e e gravavel pelo processo da aplicacao.');
        }

        return $this->check('private_storage', 'warn', 'Storage privado deve existir e ser gravavel pelos workers no ambiente alvo.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function tableCheck(string $table): array
    {
        if (Schema::hasTable($table)) {
            return $this->check($table.'_table', 'pass', "Tabela {$table} existe.");
        }

        return $this->check($table.'_table', 'fail', "Tabela {$table} em falta para queue database.");
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function check(string $name, string $status, string $message): array
    {
        return [
            'name' => $name,
            'status' => $status,
            'message' => $message,
        ];
    }
}
