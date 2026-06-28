<?php

namespace App\Data\Analytics;

final readonly class ChartDatasetData
{
    /**
     * @param  list<array{label: string, value: int|float, description?: string}>  $items
     */
    public function __construct(
        public string $type,
        public string $title,
        public string $description,
        public array $items,
    ) {}

    /**
     * @return array{type: string, title: string, description: string, items: list<array{label: string, value: int|float, description?: string}>, total: int|float}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'items' => $this->items,
            'total' => array_sum(array_map(
                fn (array $item): int|float => $item['value'],
                $this->items,
            )),
        ];
    }
}
