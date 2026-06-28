<?php

namespace App\Data\Cases;

class CaseDocumentData
{
    public function __construct(
        public readonly string $label,
        public readonly string $status,
        public readonly string $description,
        public readonly ?string $route = null,
        public readonly mixed $routeParameter = null,
    ) {}

    /**
     * @return array{label: string, status: string, description: string, route: string|null, route_parameter: mixed}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'status' => $this->status,
            'description' => $this->description,
            'route' => $this->route,
            'route_parameter' => $this->routeParameter,
        ];
    }
}
