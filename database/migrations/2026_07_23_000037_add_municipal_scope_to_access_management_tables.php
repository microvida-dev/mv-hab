<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->foreignId('municipality_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('municipal_teams', function (Blueprint $table): void {
            $table->foreignId('municipality_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('access_change_events', function (Blueprint $table): void {
            $table->foreignId('municipality_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('access_change_events', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('municipality_id');
        });

        Schema::table('municipal_teams', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('municipality_id');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('municipality_id');
        });
    }
};
