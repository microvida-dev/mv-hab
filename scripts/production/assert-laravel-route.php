#!/usr/bin/env php
<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "ROUTE_ASSERTION=FAIL\nERROR=CLI_REQUIRED\n");
    exit(1);
}

if ($argc !== 4) {
    fwrite(
        STDERR,
        "ROUTE_ASSERTION=FAIL\n"
        ."USAGE=assert-laravel-route.php <route-name> <expected-uri> <required-method>\n"
    );
    exit(1);
}

[, $routeName, $expectedUri, $requiredMethod] = $argv;

$requiredMethod = strtoupper($requiredMethod);
$root = dirname(__DIR__, 2);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$route = app('router')->getRoutes()->getByName($routeName);

if ($route === null) {
    fwrite(STDERR, "ROUTE_ASSERTION=FAIL\nERROR=ROUTE_NOT_FOUND\nROUTE_NAME={$routeName}\n");
    exit(1);
}

$methods = $route->methods();

echo "ROUTE_NAME={$route->getName()}\n";
echo "ROUTE_URI={$route->uri()}\n";
echo 'ROUTE_METHODS='.implode(',', $methods)."\n";

if ($route->uri() !== $expectedUri) {
    fwrite(
        STDERR,
        "ROUTE_ASSERTION=FAIL\n"
        ."ERROR=UNEXPECTED_URI\n"
        ."EXPECTED_URI={$expectedUri}\n"
        ."ACTUAL_URI={$route->uri()}\n"
    );
    exit(1);
}

if (! in_array($requiredMethod, $methods, true)) {
    fwrite(
        STDERR,
        "ROUTE_ASSERTION=FAIL\n"
        ."ERROR=REQUIRED_METHOD_MISSING\n"
        ."REQUIRED_METHOD={$requiredMethod}\n"
    );
    exit(1);
}

echo "ROUTE_ASSERTION=PASS\n";
