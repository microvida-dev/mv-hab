<?php

namespace App\Data\Analytics;

final readonly class FunnelStepData
{
    public function __construct(
        public string $label,
        public int $value,
        public string $description,
        public string $status = 'neutral',
    ) {}

    /**
     * @return array{label: string, value: int, description: string, status: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
