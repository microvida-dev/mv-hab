<?php

namespace Database\Factories;

use App\Models\LandlordDashboardSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LandlordDashboardSnapshot> */
class LandlordDashboardSnapshotFactory extends Factory
{
    protected $model = LandlordDashboardSnapshot::class;

    public function definition(): array
    {
        return [
            'snapshot_date' => now()->toDateString(),
            'status' => 'generated',
            'generated_at' => now(),
            'total_tenants' => 0,
            'active_contracts' => 0,
            'active_invoices' => 0,
            'overdue_invoices' => 0,
            'open_maintenance_requests' => 0,
            'scheduled_inspections' => 0,
            'unread_tenant_messages' => 0,
            'monthly_billed' => 0,
            'monthly_collected' => 0,
            'payload' => [],
        ];
    }
}
