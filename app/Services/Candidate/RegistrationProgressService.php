<?php

namespace App\Services\Candidate;

use App\Models\AdhesionRegistration;
use App\Models\Household;

class RegistrationProgressService
{
    /**
     * @return array<string|int, mixed>
     */
    public function calculate(?AdhesionRegistration $registration): array
    {
        if ($registration === null) {
            return [
                'overall' => 0,
                'sections' => $this->emptySections(),
                'missing' => ['Iniciar o Registo de Adesão'],
                'next_step' => 'Inicie o seu Registo de Adesão.',
                'totals' => [
                    'members' => 0,
                    'monthly' => 0.0,
                    'annual' => 0.0,
                ],
                'housing_summary' => null,
            ];
        }

        $registration->loadMissing([
            'household.members.incomeRecords',
            'household.incomeRecords',
            'currentHousingSituation',
        ]);

        $household = $registration->household;
        $completionPercentage = (int) $registration->completionPercentage();

        $sections = [
            'user' => [
                'label' => 'Utilizador',
                'percentage' => $completionPercentage,
                'complete' => $completionPercentage >= 100,
                'route' => 'candidate.registration.show',
                'missing' => 'Complete os dados do Registo de Adesão.',
            ],
            'household' => $this->householdSection($household),
            'income' => $this->incomeSection($household),
            'housing' => $this->housingSection($registration),
        ];

        $missing = collect($sections)
            ->reject(fn (array $section) => $section['complete'] ?? false)
            ->map(fn (array $section) => $section['missing'] ?? null)
            ->filter()
            ->values()
            ->all();

        $totals = [
            'members' => $household?->members->count() ?? 0,
            'monthly' => $household ? (float) $household->incomeRecords->sum('monthly_amount') : 0.0,
            'annual' => $household ? (float) $household->incomeRecords->sum('annual_amount') : 0.0,
        ];

        return [
            'overall' => (int) round((float) collect($sections)->avg('percentage')),
            'sections' => $sections,
            'missing' => $missing,
            'next_step' => $missing[0] ?? 'Os dados preparatórios do registo estão completos.',
            'totals' => $totals,
            'housing_summary' => $registration->currentHousingSituation?->housing_status?->label(),
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function householdSection(?Household $household): array
    {
        $complete = $household !== null
            && $household->members->isNotEmpty()
            && $household->members->contains('is_applicant', true);

        return [
            'label' => 'Agregado',
            'percentage' => $complete ? 100 : 0,
            'complete' => $complete,
            'route' => 'candidate.household.show',
            'missing' => 'Adicione os elementos do seu agregado familiar.',
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function incomeSection(?Household $household): array
    {
        if ($household === null || $household->members->isEmpty()) {
            $complete = false;
            $percentage = 0;
        } else {
            $completeMembers = $household->members
                ->filter(fn ($member) => $member->has_no_income || $member->incomeRecords->isNotEmpty())
                ->count();

            $percentage = (int) round(($completeMembers / $household->members->count()) * 100);
            $complete = $percentage >= 100;
        }

        return [
            'label' => 'Rendimentos',
            'percentage' => $percentage,
            'complete' => $complete,
            'route' => 'candidate.income-records.index',
            'missing' => 'Declare os rendimentos dos elementos do agregado.',
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function housingSection(AdhesionRegistration $registration): array
    {
        $complete = $registration->currentHousingSituation !== null;

        return [
            'label' => 'Habitação atual',
            'percentage' => $complete ? 100 : 0,
            'complete' => $complete,
            'route' => 'candidate.current-housing.show',
            'missing' => 'Preencha a informação sobre a sua situação habitacional atual.',
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function emptySections(): array
    {
        return [
            'user' => [
                'label' => 'Utilizador',
                'percentage' => 0,
                'complete' => false,
                'route' => 'candidate.registration.create',
                'missing' => 'Iniciar o Registo de Adesão.',
            ],
            'household' => [
                'label' => 'Agregado',
                'percentage' => 0,
                'complete' => false,
                'route' => 'candidate.registration.create',
                'missing' => 'Adicione os elementos do seu agregado familiar.',
            ],
            'income' => [
                'label' => 'Rendimentos',
                'percentage' => 0,
                'complete' => false,
                'route' => 'candidate.registration.create',
                'missing' => 'Declare os rendimentos dos elementos do agregado.',
            ],
            'housing' => [
                'label' => 'Habitação atual',
                'percentage' => 0,
                'complete' => false,
                'route' => 'candidate.registration.create',
                'missing' => 'Preencha a informação sobre a sua situação habitacional atual.',
            ],
        ];
    }
}
