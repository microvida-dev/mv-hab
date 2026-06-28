<?php

namespace App\Data\Cases;

class CaseSearchResultData
{
    public function __construct(
        public readonly string $label,
        public readonly string $description,
        public readonly string $section,
        public readonly string $anchor,
    ) {}

    /**
     * @return array{label: string, description: string, section: string, anchor: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'description' => $this->description,
            'section' => $this->section,
            'anchor' => $this->anchor,
        ];
    }
}
