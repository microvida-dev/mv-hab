<?php

namespace App\Data\Cases;

class CaseRelationData
{
    /**
     * @param  array<int|string, mixed>  $parameters
     */
    public function __construct(
        public readonly string $label,
        public readonly string $type,
        public readonly string $description,
        public readonly ?string $route = null,
        public readonly array $parameters = [],
        public readonly string $status = 'neutral',
    ) {}

    /**
     * @return array{label: string, type: string, description: string, route: string|null, parameters: array<int|string, mixed>, status: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'type' => $this->type,
            'description' => $this->description,
            'route' => $this->route,
            'parameters' => $this->parameters,
            'status' => $this->status,
        ];
    }
}
