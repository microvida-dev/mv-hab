<?php

namespace App\Data\Cases;

class CaseSummaryData
{
    /**
     * @param  list<array{label: string, value: mixed, description?: string|null}>  $items
     */
    public function __construct(public readonly array $items) {}

    /**
     * @return list<array{label: string, value: mixed, description?: string|null}>
     */
    public function toArray(): array
    {
        return $this->items;
    }
}
