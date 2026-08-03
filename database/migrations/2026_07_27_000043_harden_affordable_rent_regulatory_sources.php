<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affordable_rent_regulatory_profiles', function (Blueprint $table): void {
            $table->unsignedSmallInteger('tax_year')->nullable()->after('additional_person_increment');
            $table->decimal('sixth_irs_bracket_upper_limit', 12, 2)->nullable()->after('tax_year');
            $table->string('irs_source_reference')->nullable()->after('sixth_irs_bracket_upper_limit');
            $table->string('irs_source_version', 120)->nullable()->after('irs_source_reference');
            $table->date('irs_effective_from')->nullable()->after('irs_source_version');
            $table->date('irs_effective_until')->nullable()->after('irs_effective_from');
        });

        Schema::create('rent_limit_table_manifests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('regulatory_profile_id')
                ->constrained('affordable_rent_regulatory_profiles')
                ->restrictOnDelete();
            $table->foreignId('rent_rule_set_id')
                ->unique()
                ->constrained('rent_rule_sets')
                ->restrictOnDelete();
            $table->text('source_document');
            $table->string('source_reference');
            $table->string('source_version', 120);
            $table->date('effective_from')->index();
            $table->date('effective_until')->nullable()->index();
            $table->char('checksum', 64)->nullable()->index();
            $table->unsignedInteger('row_count')->default(0);
            $table->json('municipality_coverage');
            $table->json('typology_coverage');
            $table->string('validation_status', 40)->index();
            $table->boolean('demo_only')->default(false)->index();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rent_limit_table_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manifest_id')
                ->constrained('rent_limit_table_manifests')
                ->restrictOnDelete();
            $table->string('municipality_code', 80);
            $table->string('typology', 40);
            $table->decimal('minimum_rent', 12, 2)->nullable();
            $table->decimal('maximum_rent', 12, 2);
            $table->string('source_row_reference')->nullable();
            $table->timestamps();

            $table->unique(
                ['manifest_id', 'municipality_code', 'typology'],
                'rent_limit_rows_scope_unique',
            );
            $table->index(
                ['municipality_code', 'typology'],
                'rent_limit_rows_lookup_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_limit_table_rows');
        Schema::dropIfExists('rent_limit_table_manifests');

        Schema::table('affordable_rent_regulatory_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'tax_year',
                'sixth_irs_bracket_upper_limit',
                'irs_source_reference',
                'irs_source_version',
                'irs_effective_from',
                'irs_effective_until',
            ]);
        });
    }
};
