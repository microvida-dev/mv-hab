<?php

namespace App\Data\Cases;

class CaseActionData
{
    /**
     * @param  array<int|string, mixed>  $parameters
     */
    public function __construct(
        public readonly string $label,
        public readonly string $description,
        public readonly ?string $route = null,
        public readonly array $parameters = [],
        public readonly bool $enabled = false,
        public readonly string $tone = 'neutral',
    ) {}

    /**
     * @return array{label: string, description: string, route: string|null, parameters: array<int|string, mixed>, enabled: bool, tone: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'description' => $this->description,
            'route' => $this->route,
            'parameters' => $this->parameters,
            'enabled' => $this->enabled,
            'tone' => $this->tone,
        ];
    }
}
