<?php

namespace App\Data\Cases;

use Illuminate\Support\Carbon;

class CaseCommunicationData
{
    public function __construct(
        public readonly string $label,
        public readonly string $description,
        public readonly ?Carbon $date = null,
        public readonly string $source = 'processo',
    ) {}

    /**
     * @return array{label: string, description: string, date: Carbon|null, source: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'description' => $this->description,
            'date' => $this->date,
            'source' => $this->source,
        ];
    }
}
