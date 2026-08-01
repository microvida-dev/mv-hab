<?php

use App\Services\Administrative\AdministrativeDeadlineService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'corrections:expire',
    function (
        AdministrativeDeadlineService $deadlines,
    ): void {
        $expired = $deadlines->markOverdueCorrections();

        $this->info(
            $expired->count()
            .' pedido(s) de aperfeiçoamento expirado(s).',
        );
    },
)->purpose(
    'Marca pedidos de aperfeiçoamento cujo prazo terminou.',
);

Schedule::command('corrections:expire')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();
