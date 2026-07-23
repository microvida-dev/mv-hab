<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'citizens',
            'households',
            'housing_applications',
            'documents',
            'simulation_sessions',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('municipality_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'simulation_sessions',
            'documents',
            'housing_applications',
            'households',
            'citizens',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('municipality_id');
            });
        }
    }
};
