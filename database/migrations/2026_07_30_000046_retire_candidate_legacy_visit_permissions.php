<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var list<string> */
    private const RETIRED_PERMISSIONS = [
        'visits.view',
        'visits.create',
        'visits.update',
    ];

    public function up(): void
    {
        $roleIds = DB::table('roles')
            ->where('name', 'candidate')
            ->pluck('id');
        $permissionIds = DB::table('permissions')
            ->whereIn('name', self::RETIRED_PERMISSIONS)
            ->pluck('id');

        if ($roleIds->isEmpty() || $permissionIds->isEmpty()) {
            return;
        }

        DB::table('permission_role')
            ->whereIn('role_id', $roleIds)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')
            ->where('name', 'candidate')
            ->pluck('id');
        $permissionIds = DB::table('permissions')
            ->whereIn('name', self::RETIRED_PERMISSIONS)
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }
};
