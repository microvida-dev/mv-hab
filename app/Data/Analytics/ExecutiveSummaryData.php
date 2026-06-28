<?php

namespace App\Data\Analytics;

final readonly class ExecutiveSummaryData
{
    /**
     * @param  list<string>  $highlights
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $title,
        public string $description,
        public array $highlights,
        public array $warnings,
    ) {}

    /**
     * @return array{title: string, description: string, highlights: list<string>, warnings: list<string>}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'highlights' => $this->highlights,
            'warnings' => $this->warnings,
        ];
    }
}
