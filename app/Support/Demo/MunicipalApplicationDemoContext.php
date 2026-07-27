<?php

namespace App\Support\Demo;

use Carbon\CarbonImmutable;
use LogicException;

final class MunicipalApplicationDemoContext
{
    private const ALLOWED_ENVIRONMENTS = [
        'demo',
        'local',
        'testing',
    ];

    public function enabled(): bool
    {
        return (bool) config(
            'mvhab.municipal_application_demo.enabled',
            false,
        );
    }

    public function regulatoryDemoModeEnabled(): bool
    {
        return (bool) config(
            'mvhab.regulatory_demo_mode',
            false,
        );
    }

    public function assertSeederAllowed(): void
    {
        if (! in_array(
            app()->environment(),
            self::ALLOWED_ENVIRONMENTS,
            true,
        )) {
            throw new LogicException(
                'O seeder municipal só pode ser executado '
                .'em ambiente demo, local ou testing.',
            );
        }

        if (! $this->regulatoryDemoModeEnabled()) {
            throw new LogicException(
                'O seeder municipal exige '
                .'MVHAB_REGULATORY_DEMO_MODE=true.',
            );
        }

        if (! $this->enabled()) {
            throw new LogicException(
                'O seeder municipal exige '
                .'MVHAB_MUNICIPAL_APPLICATION_DEMO=true.',
            );
        }
    }

    public function referenceDate(): CarbonImmutable
    {
        $configured = config(
            'mvhab.municipal_application_demo.reference_date',
        );

        if (is_string($configured) && trim($configured) !== '') {
            return CarbonImmutable::parse(
                $configured,
                'Europe/Lisbon',
            )->startOfDay();
        }

        return CarbonImmutable::now(
            'Europe/Lisbon',
        )->startOfDay();
    }

    public function displayBanner(): bool
    {
        return $this->enabled()
            && $this->regulatoryDemoModeEnabled();
    }
}
