<?php

namespace App\Data\Cases;

use Illuminate\Support\Carbon;

class CaseHeaderData
{
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $reference,
        public readonly string $description,
        public readonly string $status,
        public readonly string $priority,
        public readonly string $responsible,
        public readonly string $team,
        public readonly string $sla,
        public readonly ?Carbon $createdAt = null,
        public readonly ?Carbon $updatedAt = null,
        public readonly ?Carbon $deadlineAt = null,
        public readonly ?string $program = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'reference' => $this->reference,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'responsible' => $this->responsible,
            'team' => $this->team,
            'sla' => $this->sla,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deadline_at' => $this->deadlineAt,
            'program' => $this->program,
        ];
    }
}
