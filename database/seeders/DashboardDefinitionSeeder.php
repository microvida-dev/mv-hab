<?php

namespace Database\Seeders;

use App\Enums\DashboardType;
use App\Models\DashboardDefinition;
use Illuminate\Database\Seeder;

class DashboardDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['operational', 'Dashboard operacional', DashboardType::Operational, null],
            ['executive', 'Dashboard executivo', DashboardType::Executive, 'reports.view_executive'],
        ] as [$code, $name, $type, $permission]) {
            $dashboard = DashboardDefinition::withTrashed()->firstOrNew(['code' => $code]);
            $dashboard->forceFill(['name' => $name, 'dashboard_type' => $type, 'required_permission' => $permission, 'is_active' => true, 'is_default' => true, 'deleted_at' => null])->save();
        }
    }
}
