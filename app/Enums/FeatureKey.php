<?php

namespace App\Enums;

enum FeatureKey: string
{
    case ApplicationIntake = 'applications.intake';
    case ApplicationReview = 'applications.review';
    case ApplicationExport = 'applications.export';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationIntake => 'Recolha de candidaturas',
            self::ApplicationReview => 'Análise de candidaturas',
            self::ApplicationExport => 'Exportação de candidaturas',
        };
    }

    /** @return list<self> */
    public function dependencies(): array
    {
        return match ($this) {
            self::ApplicationIntake => [],
            self::ApplicationReview, self::ApplicationExport => [self::ApplicationIntake],
        };
    }
}
