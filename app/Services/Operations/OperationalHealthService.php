<?php

namespace App\Services\Operations;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class OperationalHealthService
{
    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    public function checks(): array
    {
        return [
            $this->environmentCheck(),
            $this->debugCheck(),
            $this->databaseCheck(),
            $this->cacheCheck(),
            $this->queueCheck(),
            $this->failedJobsCheck(),
            $this->privateStorageCheck(),
            $this->logChannelCheck(),
            $this->scheduleAvailabilityCheck(),
            $this->routeHealthCheck(),
        ];
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
    private function environmentCheck(): array
    {
        $environment = (string) config('app.env', 'production');

        if (in_array($environment, ['local', 'testing', 'staging', 'production'], true)) {
            return $this->check('app_environment', 'pass', "Ambiente {$environment} reconhecido.");
        }

        return $this->check('app_environment', 'warn', 'Ambiente não standard; validar checklist municipal antes do piloto.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function debugCheck(): array
    {
        $debug = (bool) config('app.debug');
        $environment = (string) config('app.env', 'production');

        if (! $debug) {
            return $this->check('debug_state', 'pass', 'APP_DEBUG encontra-se desativado.');
        }

        if (in_array($environment, ['local', 'testing'], true)) {
            return $this->check('debug_state', 'warn', 'APP_DEBUG ativo apenas aceitável em local/testes.');
        }

        return $this->check('debug_state', 'fail', 'APP_DEBUG ativo não é aceitável em staging/piloto/produção.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function databaseCheck(): array
    {
        try {
            DB::connection()->getPdo();

            return $this->check('database_connection', 'pass', 'Ligação à base de dados operacional.');
        } catch (Throwable) {
            return $this->check('database_connection', 'fail', 'Ligação à base de dados indisponível.');
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function cacheCheck(): array
    {
        try {
            Cache::put('mvhab_health_probe', 'ok', 5);
            Cache::forget('mvhab_health_probe');

            return $this->check('cache_store', 'pass', 'Cache operacional.');
        } catch (Throwable) {
            return $this->check('cache_store', 'warn', 'Cache não respondeu ao probe local.');
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function queueCheck(): array
    {
        $connection = (string) config('queue.default', 'sync');
        $environment = (string) config('app.env', 'production');

        if (in_array($connection, ['database', 'redis'], true)) {
            return $this->check('queue_connection', 'pass', "Queue connection {$connection} suportada.");
        }

        if ($connection === 'sync' && in_array($environment, ['local', 'testing'], true)) {
            return $this->check('queue_connection', 'warn', 'QUEUE_CONNECTION=sync apenas aceitável em local/testes.');
        }

        return $this->check('queue_connection', 'fail', 'Queue connection não operacional para staging/piloto.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function failedJobsCheck(): array
    {
        $failed = config('queue.failed');
        $driver = is_array($failed) ? ($failed['driver'] ?? null) : null;

        if ($driver !== 'database-uuids') {
            return $this->check('failed_jobs', 'fail', 'Failed jobs devem usar persistência database-uuids.');
        }

        if (Schema::hasTable('failed_jobs')) {
            return $this->check('failed_jobs', 'pass', 'Failed jobs configurados e tabela disponível.');
        }

        return $this->check('failed_jobs', 'warn', 'Failed jobs configurados; confirmar migrations no ambiente alvo.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function privateStorageCheck(): array
    {
        $path = storage_path('app/private');

        if (is_dir($path) && is_writable($path)) {
            return $this->check('private_storage', 'pass', 'Storage privado existe e é gravável.');
        }

        return $this->check('private_storage', 'warn', 'Storage privado deve existir e ser gravável no servidor alvo.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function logChannelCheck(): array
    {
        $channel = (string) config('logging.default', '');

        if ($channel !== '') {
            return $this->check('log_channel', 'pass', "Canal de log {$channel} configurado.");
        }

        return $this->check('log_channel', 'warn', 'Canal de log não configurado explicitamente.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function scheduleAvailabilityCheck(): array
    {
        if (array_key_exists('schedule:list', Artisan::all())) {
            return $this->check('schedule_list', 'pass', 'Comando schedule:list disponível.');
        }

        return $this->check('schedule_list', 'warn', 'Comando schedule:list indisponível neste contexto.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function routeHealthCheck(): array
    {
        if (count(Route::getRoutes()->getRoutes()) > 0) {
            return $this->check('route_health', 'pass', 'Rotas carregadas pela aplicação.');
        }

        return $this->check('route_health', 'fail', 'Nenhuma rota carregada.');
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
