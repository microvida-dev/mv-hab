<?php

namespace App\Support;

final readonly class HousingTypology
{
    private function __construct(
        public int $rooms,
        public string $label,
    ) {}

    public static function from(?string $value): ?self
    {
        $normalized = strtoupper(trim((string) $value));

        if (preg_match('/^T\\s*(\\d{1,2})$/', $normalized, $matches) !== 1) {
            return null;
        }

        $rooms = (int) $matches[1];

        return new self($rooms, 'T'.$rooms);
    }

    public function compare(self $other): int
    {
        return $this->rooms <=> $other->rooms;
    }
}
