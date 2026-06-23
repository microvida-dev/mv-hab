<?php

namespace Database\Seeders;

use App\Enums\ContractStatus;
use App\Enums\HousingApplicationStatus;
use App\Enums\HousingUnitStatus;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\Citizen;
use App\Models\Contract;
use App\Models\Household;
use App\Models\HousingApplication;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $citizens = Citizen::query()->orderBy('id')->get();
        $housingUnits = HousingUnit::query()->orderBy('id')->get();

        if ($citizens->isEmpty() || $housingUnits->isEmpty()) {
            return;
        }

        foreach ($citizens as $index => $citizen) {
            $household = Household::updateOrCreate(
                [
                    'citizen_id' => $citizen->id,
                    'name' => 'Agregado '.$citizen->name,
                ],
                [
                    'monthly_income' => 850 + ($index * 125),
                    'members_count' => 2 + ($index % 3),
                    'notes' => 'Agregado criado para dados de demonstração.',
                ],
            );

            $status = match ($index) {
                0 => HousingApplicationStatus::Approved,
                1 => HousingApplicationStatus::UnderReview,
                2 => HousingApplicationStatus::Submitted,
                default => HousingApplicationStatus::Draft,
            };

            HousingApplication::updateOrCreate(
                [
                    'citizen_id' => $citizen->id,
                    'household_id' => $household->id,
                ],
                [
                    'status' => $status->value,
                    'priority_score' => 20 + ($index * 15),
                    'notes' => 'Candidatura de exemplo para validação do módulo.',
                    'submitted_at' => $status === HousingApplicationStatus::Draft ? null : now()->subDays($index + 3),
                ],
            );
        }

        $citizen = $citizens->first();
        $housingUnit = $housingUnits->firstWhere('status', HousingUnitStatus::Available) ?? $housingUnits->first();

        $contract = Contract::updateOrCreate(
            [
                'citizen_id' => $citizen->id,
                'housing_unit_id' => $housingUnit->id,
                'start_date' => now()->startOfYear()->format('Y-m-d'),
            ],
            [
                'end_date' => null,
                'monthly_rent' => 185.00,
                'status' => ContractStatus::Active->value,
            ],
        );

        $housingUnit->update(['status' => HousingUnitStatus::Occupied->value]);

        Payment::updateOrCreate(
            ['reference' => 'PAG-2026-0001'],
            [
                'contract_id' => $contract->id,
                'amount' => 185.00,
                'due_date' => now()->startOfMonth()->format('Y-m-d'),
                'paid_at' => now()->subDays(2),
                'status' => PaymentStatus::Paid->value,
            ],
        );

        Payment::updateOrCreate(
            ['reference' => 'PAG-2026-0002'],
            [
                'contract_id' => $contract->id,
                'amount' => 185.00,
                'due_date' => now()->addMonth()->startOfMonth()->format('Y-m-d'),
                'paid_at' => null,
                'status' => PaymentStatus::Pending->value,
            ],
        );

        MaintenanceRequest::updateOrCreate(
            [
                'housing_unit_id' => $housingUnit->id,
                'title' => 'Infiltracao na cozinha',
            ],
            [
                'citizen_id' => $citizen->id,
                'description' => 'Foi reportada uma infiltracao junto a banca da cozinha.',
                'priority' => MaintenancePriority::High->value,
                'status' => MaintenanceRequestStatus::Open->value,
                'reported_at' => now()->subDays(5),
                'resolved_at' => null,
            ],
        );
    }
}
