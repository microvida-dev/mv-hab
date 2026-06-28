<?php

namespace App\Data\Analytics;

final readonly class AnalyticsMetricData
{
    public function __construct(
        public string $title,
        public string $value,
        public string $period,
        public string $description,
        public string $tone = 'neutral',
        public ?string $href = null,
        public ?string $trendLabel = null,
    ) {}

    /**
     * @return array{title: string, value: string, period: string, description: string, tone: string, href: string|null, trend_label: string|null}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'value' => $this->value,
            'period' => $this->period,
            'description' => $this->description,
            'tone' => $this->tone,
            'href' => $this->href,
            'trend_label' => $this->trendLabel,
        ];
    }
}
