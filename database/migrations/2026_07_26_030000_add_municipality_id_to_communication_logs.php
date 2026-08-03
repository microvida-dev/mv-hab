<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_logs', function (Blueprint $table): void {
            $table
                ->foreignId('municipality_id')
                ->nullable()
                ->after('id')
                ->constrained('municipalities')
                ->nullOnDelete();
            $table->index(
                ['municipality_id', 'status'],
                'communication_logs_municipality_status_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('communication_logs', function (Blueprint $table): void {
            $table->dropForeign(
                'communication_logs_municipality_id_foreign',
            );
            $table->dropIndex(
                'communication_logs_municipality_status_idx',
            );
            $table->dropColumn('municipality_id');
        });
    }
};
