<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipality_feature_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key', 100);
            $table->boolean('enabled');
            $table->timestamps();

            $table->unique(
                ['municipality_id', 'feature_key'],
                'mfe_municipality_feature_unique',
            );
            $table->index(
                ['municipality_id', 'enabled'],
                'mfe_municipality_enabled_index',
            );
        });

        $features = [
            'applications.intake',
            'applications.review',
            'applications.export',
        ];

        DB::table('municipalities')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($municipalities) use ($features): void {
                $timestamp = now();
                $rows = [];

                foreach ($municipalities as $municipality) {
                    foreach ($features as $feature) {
                        $rows[] = [
                            'municipality_id' => $municipality->id,
                            'feature_key' => $feature,
                            'enabled' => true,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('municipality_feature_entitlements')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipality_feature_entitlements');
    }
};
