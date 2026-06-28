<?php

namespace App\Data\Analytics;

final readonly class SlaBucketData
{
    public function __construct(
        public string $label,
        public int $value,
        public string $status,
        public string $description,
    ) {}

    /**
     * @return array{label: string, value: int, status: string, description: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
            'status' => $this->status,
            'description' => $this->description,
        ];
    }
}
