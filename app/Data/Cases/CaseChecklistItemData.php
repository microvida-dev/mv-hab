<?php

namespace App\Data\Cases;

class CaseChecklistItemData
{
    public function __construct(
        public readonly string $label,
        public readonly string $status,
        public readonly string $description,
    ) {}

    /**
     * @return array{label: string, status: string, description: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'status' => $this->status,
            'description' => $this->description,
        ];
    }
}
