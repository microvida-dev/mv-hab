<?php

namespace App\Services\Cases;

use Illuminate\Database\Eloquent\Model;

class CaseResolver
{
    public function __construct(private readonly CaseTypeRegistry $registry) {}

    public function resolve(string $type, int|string $key): ?Model
    {
        $config = $this->registry->get($type);
        $modelClass = $config['model'] ?? null;

        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        /** @var Model $instance */
        $instance = new $modelClass;
        $routeKey = $instance->getRouteKeyName();

        /** @var Model|null $case */
        $case = $modelClass::query()
            ->where($routeKey, $key)
            ->first();

        return $case;
    }

    public function typeFor(Model $case): ?string
    {
        return $this->registry->typeFor($case);
    }
}
