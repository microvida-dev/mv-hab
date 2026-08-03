<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MUNICIPALITY_INDEX =
        'roles_municipality_lookup_index';

    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('template_key', 120)->nullable()->after('municipality_id');
            $table->string('template_version', 32)->nullable()->after('template_key');
            $table->char('template_fingerprint', 64)->nullable()->after('template_version');

            $table->unique(
                ['municipality_id', 'template_key'],
                'roles_municipality_template_unique',
            );
        });

        $this->ensureMunicipalityIndex();
    }

    public function down(): void
    {
        if (DB::table('roles')
            ->where(function ($query): void {
                $query->whereNotNull('template_key')
                    ->orWhereNotNull('template_version')
                    ->orWhereNotNull('template_fingerprint');
            })
            ->exists()) {
            throw new RuntimeException(
                'O rollback foi recusado porque existem perfis municipais com metadata de template.',
            );
        }

        $this->ensureMunicipalityIndex();

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique('roles_municipality_template_unique');
            $table->dropColumn([
                'template_key',
                'template_version',
                'template_fingerprint',
            ]);
        });
    }

    private function ensureMunicipalityIndex(): void
    {
        if (Schema::hasIndex('roles', self::MUNICIPALITY_INDEX)) {
            return;
        }

        Schema::table('roles', function (Blueprint $table): void {
            $table->index(
                'municipality_id',
                self::MUNICIPALITY_INDEX,
            );
        });
    }
};
