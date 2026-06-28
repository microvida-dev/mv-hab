<?php

namespace App\Data\Cases;

use Illuminate\Support\Carbon;

class CaseTimelineItemData
{
    public function __construct(
        public readonly ?Carbon $date,
        public readonly string $type,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $source,
        public readonly ?string $actor = null,
    ) {}

    /**
     * @return array{date: Carbon|null, type: string, title: string, description: string|null, source: string, actor: string|null}
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'source' => $this->source,
            'actor' => $this->actor,
        ];
    }
}
