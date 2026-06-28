<?php

namespace App\Data\Cases;

use Illuminate\Support\Carbon;

class CaseTaskData
{
    /**
     * @param  array<int|string, mixed>  $parameters
     */
    public function __construct(
        public readonly string $label,
        public readonly string $status,
        public readonly string $priority,
        public readonly ?Carbon $dueAt = null,
        public readonly ?string $route = null,
        public readonly array $parameters = [],
    ) {}

    /**
     * @return array{label: string, status: string, priority: string, due_at: Carbon|null, route: string|null, parameters: array<int|string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_at' => $this->dueAt,
            'route' => $this->route,
            'parameters' => $this->parameters,
        ];
    }
}
