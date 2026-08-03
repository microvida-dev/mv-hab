<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Bus;
use Tests\Support\Queue\InterruptibleProgram53Job;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$markerPath = $argv[1] ?? null;
if (! is_string($markerPath) || trim($markerPath) === '') {
    fwrite(STDERR, 'Marker path obrigatório.'.PHP_EOL);

    exit(2);
}

Bus::dispatch(new InterruptibleProgram53Job($markerPath));
