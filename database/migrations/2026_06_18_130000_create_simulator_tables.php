<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('adhesion_registration_id')->nullable();
            $table->unsignedBigInteger('application_id')->nullable();
            $table->string('scope', 80)->index();
            $table->string('status', 80)->default('draft')->index();
            $table->string('result_status', 80)->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('saved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->string('source', 80)->default('web')->index();
            $table->string('ip_hash', 128)->nullable();
            $table->string('user_agent_hash', 128)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id', 'sim_sessions_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('adhesion_registration_id', 'sim_sessions_registration_fk')->references('id')->on('adhesion_registrations')->nullOnDelete();
            $table->foreign('application_id', 'sim_sessions_application_fk')->references('id')->on('applications')->nullOnDelete();
            $table->index(['user_id', 'status'], 'sim_sessions_user_status_idx');
            $table->index(['scope', 'status'], 'sim_sessions_scope_status_idx');
        });

        Schema::create('simulation_input_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_session_id');
            $table->unsignedSmallInteger('household_members_count')->nullable();
            $table->unsignedSmallInteger('adults_count')->nullable();
            $table->unsignedSmallInteger('dependents_count')->nullable();
            $table->unsignedSmallInteger('disabled_members_count')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->decimal('annual_income', 12, 2)->nullable();
            $table->decimal('current_monthly_rent', 12, 2)->nullable();
            $table->string('housing_status', 100)->nullable();
            $table->json('preferred_parishes')->nullable();
            $table->json('preferred_typologies')->nullable();
            $table->json('input_data')->nullable();
            $table->decimal('completeness_score', 5, 2)->default(0);
            $table->boolean('contains_personal_data')->default(false);
            $table->timestamps();

            $table->foreign('simulation_session_id', 'sim_inputs_session_fk')->references('id')->on('simulation_sessions')->cascadeOnDelete();
            $table->unique('simulation_session_id', 'sim_inputs_session_unique');
        });

        Schema::create('simulation_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_session_id');
            $table->string('result_status', 80)->index();
            $table->text('eligibility_summary')->nullable();
            $table->decimal('eligibility_score', 5, 2)->nullable();
            $table->json('eligibility_payload')->nullable();
            $table->string('typology_status', 80)->nullable();
            $table->string('recommended_typology', 100)->nullable();
            $table->unsignedTinyInteger('recommended_bedrooms')->nullable();
            $table->json('typology_payload')->nullable();
            $table->string('rent_status', 80)->nullable();
            $table->decimal('estimated_rent_min', 12, 2)->nullable();
            $table->decimal('estimated_rent_max', 12, 2)->nullable();
            $table->decimal('estimated_effort_rate', 5, 2)->nullable();
            $table->json('rent_payload')->nullable();
            $table->json('recommendations_payload')->nullable();
            $table->unsignedInteger('impediments_count')->default(0);
            $table->unsignedInteger('blocking_impediments_count')->default(0);
            $table->unsignedInteger('recommended_contests_count')->default(0);
            $table->timestamps();

            $table->foreign('simulation_session_id', 'sim_results_session_fk')->references('id')->on('simulation_sessions')->cascadeOnDelete();
            $table->unique('simulation_session_id', 'sim_results_session_unique');
        });

        Schema::create('simulation_impediments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_session_id');
            $table->unsignedBigInteger('simulation_result_id')->nullable();
            $table->string('type', 120)->index();
            $table->string('severity', 80)->index();
            $table->string('code', 120)->nullable()->index();
            $table->string('title');
            $table->text('message');
            $table->text('recommendation')->nullable();
            $table->boolean('is_blocking')->default(false)->index();
            $table->string('related_field')->nullable();
            $table->nullableMorphs('related_model', 'sim_imp_related_idx');
            $table->timestamps();

            $table->foreign('simulation_session_id', 'sim_imp_session_fk')->references('id')->on('simulation_sessions')->cascadeOnDelete();
            $table->foreign('simulation_result_id', 'sim_imp_result_fk')->references('id')->on('simulation_results')->cascadeOnDelete();
            $table->index(['simulation_session_id', 'is_blocking'], 'sim_imp_session_blocking_idx');
        });

        Schema::create('simulation_recommended_contests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_session_id');
            $table->unsignedBigInteger('simulation_result_id')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id');
            $table->string('match_status', 80)->index();
            $table->decimal('match_score', 5, 2)->default(0);
            $table->string('public_status', 80)->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->json('recommended_typologies')->nullable();
            $table->decimal('rent_min', 12, 2)->nullable();
            $table->decimal('rent_max', 12, 2)->nullable();
            $table->json('reasons')->nullable();
            $table->json('warnings')->nullable();
            $table->string('cta_url')->nullable();
            $table->timestamps();

            $table->foreign('simulation_session_id', 'sim_rec_session_fk')->references('id')->on('simulation_sessions')->cascadeOnDelete();
            $table->foreign('simulation_result_id', 'sim_rec_result_fk')->references('id')->on('simulation_results')->cascadeOnDelete();
            $table->foreign('program_id', 'sim_rec_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'sim_rec_contest_fk')->references('id')->on('contests')->cascadeOnDelete();
            $table->index(['simulation_session_id', 'match_score'], 'sim_rec_session_score_idx');
        });

        Schema::create('candidate_data_reuse_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('adhesion_registration_id')->nullable();
            $table->string('profile_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->json('registration_snapshot')->nullable();
            $table->json('household_snapshot')->nullable();
            $table->json('income_snapshot')->nullable();
            $table->json('housing_snapshot')->nullable();
            $table->json('documents_snapshot')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamp('last_confirmed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_from_simulation_session_id')->nullable();
            $table->unsignedBigInteger('created_from_application_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id', 'reuse_profiles_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('adhesion_registration_id', 'reuse_profiles_registration_fk')->references('id')->on('adhesion_registrations')->nullOnDelete();
            $table->foreign('created_from_simulation_session_id', 'reuse_profiles_sim_fk')->references('id')->on('simulation_sessions')->nullOnDelete();
            $table->foreign('created_from_application_id', 'reuse_profiles_app_fk')->references('id')->on('applications')->nullOnDelete();
            $table->index(['user_id', 'status'], 'reuse_profiles_user_status_idx');
        });

        Schema::create('application_prefills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('application_id')->nullable();
            $table->unsignedBigInteger('simulation_session_id')->nullable();
            $table->unsignedBigInteger('candidate_data_reuse_profile_id')->nullable();
            $table->string('status', 80)->default('pending_confirmation')->index();
            $table->json('prefill_payload')->nullable();
            $table->json('fields_included')->nullable();
            $table->json('fields_excluded')->nullable();
            $table->json('warnings')->nullable();
            $table->timestamp('confirmed_by_user_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id', 'prefills_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('application_id', 'prefills_application_fk')->references('id')->on('applications')->nullOnDelete();
            $table->foreign('simulation_session_id', 'prefills_session_fk')->references('id')->on('simulation_sessions')->nullOnDelete();
            $table->foreign('candidate_data_reuse_profile_id', 'prefills_reuse_fk')->references('id')->on('candidate_data_reuse_profiles')->nullOnDelete();
            $table->index(['user_id', 'status'], 'prefills_user_status_idx');
        });

        Schema::create('registration_renewals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('adhesion_registration_id');
            $table->string('renewal_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->string('reason', 120)->nullable();
            $table->json('previous_snapshot')->nullable();
            $table->json('updated_snapshot')->nullable();
            $table->json('changed_fields')->nullable();
            $table->json('missing_fields')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id', 'renewals_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('adhesion_registration_id', 'renewals_registration_fk')->references('id')->on('adhesion_registrations')->cascadeOnDelete();
            $table->index(['user_id', 'status'], 'renewals_user_status_idx');
        });

        Schema::create('simulator_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('municipality_id')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('anonymous_simulator_enabled')->default(true);
            $table->boolean('candidate_simulator_enabled')->default(true);
            $table->unsignedSmallInteger('max_recommended_contests')->default(5);
            $table->decimal('default_effort_rate', 5, 2)->default(35);
            $table->unsignedSmallInteger('session_retention_days')->default(30);
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('municipality_id', 'sim_cfg_municipality_fk')->references('id')->on('municipalities')->nullOnDelete();
            $table->foreign('program_id', 'sim_cfg_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'sim_cfg_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('created_by', 'sim_cfg_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'sim_cfg_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['program_id', 'is_active'], 'sim_cfg_program_active_idx');
            $table->index(['contest_id', 'is_active'], 'sim_cfg_contest_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulator_configurations');
        Schema::dropIfExists('registration_renewals');
        Schema::dropIfExists('application_prefills');
        Schema::dropIfExists('candidate_data_reuse_profiles');
        Schema::dropIfExists('simulation_recommended_contests');
        Schema::dropIfExists('simulation_impediments');
        Schema::dropIfExists('simulation_results');
        Schema::dropIfExists('simulation_input_snapshots');
        Schema::dropIfExists('simulation_sessions');
    }
};
