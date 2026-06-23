<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('housing_units', 'public_slug')) {
            return;
        }

        Schema::table('housing_units', function (Blueprint $table): void {
            $table->foreignId('municipality_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('public_reference')->nullable()->unique();
            $table->string('public_title')->nullable();
            $table->string('public_slug')->nullable()->unique();
            $table->string('public_summary', 500)->nullable();
            $table->text('public_description')->nullable();
            $table->string('parish')->nullable()->index();
            $table->string('locality')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('floor', 50)->nullable();
            $table->decimal('gross_area_sqm', 8, 2)->nullable();
            $table->decimal('usable_area_sqm', 8, 2)->nullable();
            $table->string('energy_rating', 20)->nullable();
            $table->string('public_location_description')->nullable();
            $table->boolean('public_address_visible')->default(false);
            $table->decimal('public_latitude', 10, 7)->nullable();
            $table->decimal('public_longitude', 10, 7)->nullable();
            $table->string('public_location_precision')->default('parish')->index();
            $table->string('public_status')->default('available')->index();
            $table->string('public_visibility_status')->default('draft')->index();
            $table->boolean('is_public')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('unpublished_at')->nullable();
            $table->unsignedInteger('public_sort_order')->default(0)->index();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('og_image_path')->nullable();

            $table->index(['is_public', 'public_visibility_status', 'published_at'], 'hu_public_state_idx');
            $table->index(['public_latitude', 'public_longitude'], 'hu_public_coords_idx');
            $table->index(['typology', 'monthly_rent'], 'hu_typology_rent_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('housing_units', 'public_slug')) {
            return;
        }

        Schema::table('housing_units', function (Blueprint $table): void {
            $table->dropForeign(['municipality_id']);

            $table->dropColumn([
                'municipality_id',
                'public_reference',
                'public_title',
                'public_slug',
                'public_summary',
                'public_description',
                'parish',
                'locality',
                'postal_code',
                'floor',
                'gross_area_sqm',
                'usable_area_sqm',
                'energy_rating',
                'public_location_description',
                'public_address_visible',
                'public_latitude',
                'public_longitude',
                'public_location_precision',
                'public_status',
                'public_visibility_status',
                'is_public',
                'published_at',
                'unpublished_at',
                'public_sort_order',
                'seo_title',
                'seo_description',
                'og_image_path',
            ]);
        });
    }
};
