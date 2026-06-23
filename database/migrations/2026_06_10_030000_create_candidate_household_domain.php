<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->dropForeign(['citizen_id']);
        });

        Schema::table('households', function (Blueprint $table) {
            $table->foreignId('citizen_id')->nullable()->change();
            $table->unsignedBigInteger('adhesion_registration_id')->nullable()->after('citizen_id');
            $table->string('household_type', 50)->default('family')->after('name');
            $table->softDeletes();

            $table->foreign('citizen_id', 'households_citizen_id_foreign')
                ->references('id')
                ->on('citizens')
                ->cascadeOnDelete();
            $table->foreign('adhesion_registration_id', 'households_registration_fk')
                ->references('id')
                ->on('adhesion_registrations')
                ->cascadeOnDelete();
            $table->unique('adhesion_registration_id', 'households_registration_unique');
        });

        Schema::create('household_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('household_id');
            $table->unsignedBigInteger('adhesion_registration_id');

            $table->boolean('is_applicant')->default(false);
            $table->string('full_name');
            $table->date('birth_date')->nullable();
            $table->string('gender', 50)->nullable();
            $table->string('relationship', 50);
            $table->string('nationality', 100)->nullable();

            $table->string('document_type', 50)->nullable();
            $table->string('document_number', 100)->nullable();
            $table->date('document_valid_until')->nullable();
            $table->string('nif', 20)->nullable();

            $table->string('marital_status', 100)->nullable();
            $table->string('professional_status', 50)->nullable();
            $table->string('employment_type', 100)->nullable();
            $table->string('employer_name')->nullable();
            $table->string('workplace_municipality', 100)->nullable();
            $table->boolean('works_in_municipality')->default(false);

            $table->boolean('is_dependent')->default(false);
            $table->boolean('is_student')->default(false);
            $table->boolean('is_disabled')->default(false);
            $table->decimal('disability_percentage', 5, 2)->nullable();
            $table->boolean('has_reduced_mobility')->default(false);
            $table->boolean('is_informal_caregiver')->default(false);
            $table->boolean('is_elderly')->default(false);

            $table->decimal('monthly_declared_income', 12, 2)->default(0);
            $table->decimal('annual_declared_income', 12, 2)->default(0);
            $table->boolean('has_no_income')->default(false);
            $table->string('no_income_reason', 1000)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('household_id', 'household_members_household_fk')
                ->references('id')
                ->on('households')
                ->cascadeOnDelete();
            $table->foreign('adhesion_registration_id', 'household_members_registration_fk')
                ->references('id')
                ->on('adhesion_registrations')
                ->cascadeOnDelete();
            $table->unique(['household_id', 'nif'], 'household_members_household_nif_unique');
        });

        Schema::create('income_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('income_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('household_member_id');
            $table->unsignedBigInteger('household_id');
            $table->unsignedBigInteger('adhesion_registration_id');
            $table->unsignedBigInteger('income_source_id');

            $table->string('description')->nullable();
            $table->decimal('monthly_amount', 12, 2);
            $table->decimal('annual_amount', 12, 2);
            $table->unsignedSmallInteger('reference_year')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->boolean('is_taxable')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('household_member_id', 'income_records_member_fk')
                ->references('id')
                ->on('household_members')
                ->cascadeOnDelete();
            $table->foreign('household_id', 'income_records_household_fk')
                ->references('id')
                ->on('households')
                ->cascadeOnDelete();
            $table->foreign('adhesion_registration_id', 'income_records_registration_fk')
                ->references('id')
                ->on('adhesion_registrations')
                ->cascadeOnDelete();
            $table->foreign('income_source_id', 'income_records_source_fk')
                ->references('id')
                ->on('income_sources')
                ->restrictOnDelete();
        });

        Schema::create('current_housing_situations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('adhesion_registration_id');
            $table->string('housing_status', 50);
            $table->string('current_address')->nullable();
            $table->string('current_postal_code', 20)->nullable();
            $table->string('current_city', 100)->nullable();
            $table->string('current_parish', 100)->nullable();
            $table->string('current_municipality', 100)->nullable();

            $table->boolean('resides_in_municipality')->default(false);
            $table->decimal('residence_years_in_municipality', 5, 2)->nullable();
            $table->boolean('works_in_municipality')->default(false);
            $table->string('workplace_municipality', 100)->nullable();

            $table->string('current_housing_typology', 50)->nullable();
            $table->unsignedTinyInteger('current_housing_rooms')->nullable();
            $table->string('current_housing_condition', 50)->nullable();
            $table->decimal('current_monthly_rent', 12, 2)->nullable();
            $table->decimal('current_housing_expense', 12, 2)->nullable();

            $table->boolean('is_overcrowded')->default(false);
            $table->boolean('is_at_risk_of_eviction')->default(false);
            $table->boolean('is_homeless')->default(false);
            $table->boolean('is_temporary_accommodation')->default(false);
            $table->boolean('is_domestic_violence_victim')->default(false);
            $table->boolean('has_accessibility_needs')->default(false);
            $table->boolean('has_high_rent_burden')->default(false);

            $table->text('request_reason')->nullable();
            $table->text('additional_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('adhesion_registration_id', 'current_housing_registration_fk')
                ->references('id')
                ->on('adhesion_registrations')
                ->cascadeOnDelete();
            $table->unique('adhesion_registration_id', 'current_housing_registration_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('current_housing_situations');
        Schema::dropIfExists('income_records');
        Schema::dropIfExists('income_sources');
        Schema::dropIfExists('household_members');

        DB::table('households')->whereNull('citizen_id')->delete();

        Schema::table('households', function (Blueprint $table) {
            $table->dropForeign('households_registration_fk');
            $table->dropUnique('households_registration_unique');
            $table->dropForeign('households_citizen_id_foreign');
            $table->dropColumn(['adhesion_registration_id', 'household_type', 'deleted_at']);
            $table->foreignId('citizen_id')->nullable(false)->change();
            $table->foreign('citizen_id', 'households_citizen_id_foreign')
                ->references('id')
                ->on('citizens')
                ->cascadeOnDelete();
        });
    }
};
