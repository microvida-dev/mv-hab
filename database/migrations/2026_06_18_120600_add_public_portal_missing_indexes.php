<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('housing_units')) {
            Schema::table('housing_units', function (Blueprint $table): void {
                if (! $this->indexExists('housing_units', 'hu_public_state_idx')) {
                    $table->index(['is_public', 'public_visibility_status', 'published_at'], 'hu_public_state_idx');
                }

                if (! $this->indexExists('housing_units', 'hu_public_coords_idx')) {
                    $table->index(['public_latitude', 'public_longitude'], 'hu_public_coords_idx');
                }

                if (! $this->indexExists('housing_units', 'hu_typology_rent_idx')) {
                    $table->index(['typology', 'monthly_rent'], 'hu_typology_rent_idx');
                }
            });
        }

        if (Schema::hasTable('housing_unit_public_documents')) {
            Schema::table('housing_unit_public_documents', function (Blueprint $table): void {
                if (! $this->indexExists('housing_unit_public_documents', 'hupd_unit_public_sort_idx')) {
                    $table->index(['housing_unit_id', 'is_public', 'sort_order'], 'hupd_unit_public_sort_idx');
                }

                if (! $this->indexExists('housing_unit_public_documents', 'hupd_contest_public_idx')) {
                    $table->index(['contest_id', 'is_public'], 'hupd_contest_public_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('housing_units')) {
            Schema::table('housing_units', function (Blueprint $table): void {
                foreach (['hu_public_state_idx', 'hu_public_coords_idx', 'hu_typology_rent_idx'] as $index) {
                    if ($this->indexExists('housing_units', $index)) {
                        $table->dropIndex($index);
                    }
                }
            });
        }

        if (Schema::hasTable('housing_unit_public_documents')) {
            Schema::table('housing_unit_public_documents', function (Blueprint $table): void {
                foreach (['hupd_unit_public_sort_idx', 'hupd_contest_public_idx'] as $index) {
                    if ($this->indexExists('housing_unit_public_documents', $index)) {
                        $table->dropIndex($index);
                    }
                }
            });
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => ($index['name'] ?? null) === $name);
    }
};
