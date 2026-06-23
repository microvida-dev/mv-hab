<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 150)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 60)->index();
            $table->string('value_type', 60);
            $table->string('calculation_service');
            $table->string('calculation_method', 150);
            $table->string('required_permission')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->json('default_filters')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dashboard_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 150)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('dashboard_type', 60)->index();
            $table->string('required_permission')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false);
            $table->json('default_filters')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('created_by', 'dd_creator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'dd_updater_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dashboard_definition_id');
            $table->unsignedBigInteger('indicator_definition_id')->nullable();
            $table->string('code', 150);
            $table->string('title');
            $table->string('widget_type', 60);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('width')->default(1);
            $table->string('required_permission')->nullable();
            $table->json('configuration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['dashboard_definition_id', 'code'], 'dw_dashboard_code_unique');
            $table->foreign('dashboard_definition_id', 'dw_dashboard_fk')->references('id')->on('dashboard_definitions')->cascadeOnDelete();
            $table->foreign('indicator_definition_id', 'dw_indicator_fk')->references('id')->on('indicator_definitions')->nullOnDelete();
        });

        Schema::create('indicator_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('indicator_definition_id');
            $table->decimal('value_numeric', 20, 4)->nullable();
            $table->text('value_text')->nullable();
            $table->json('value_json')->nullable();
            $table->json('filters')->nullable();
            $table->string('filters_hash', 64)->index();
            $table->string('status', 60)->default('available')->index();
            $table->timestamp('calculated_at')->index();
            $table->unsignedBigInteger('calculated_by')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->foreign('indicator_definition_id', 'is_indicator_fk')->references('id')->on('indicator_definitions')->cascadeOnDelete();
            $table->foreign('calculated_by', 'is_user_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 150)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('report_type', 60)->index();
            $table->string('sensitivity_level', 60)->index();
            $table->string('required_permission')->nullable();
            $table->string('query_service');
            $table->string('query_method', 150);
            $table->json('available_formats');
            $table->json('available_scopes');
            $table->json('filter_schema')->nullable();
            $table->boolean('requires_filters')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('created_by', 'rd_creator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'rd_updater_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('report_filter_presets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_definition_id');
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->json('filters');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['report_definition_id', 'user_id', 'name'], 'rfp_report_user_name_unique');
            $table->foreign('report_definition_id', 'rfp_report_fk')->references('id')->on('report_definitions')->cascadeOnDelete();
            $table->foreign('user_id', 'rfp_user_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('report_definition_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 60)->index();
            $table->string('format', 20);
            $table->string('scope', 60);
            $table->json('filters');
            $table->unsignedBigInteger('row_count')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->foreign('report_definition_id', 'rr_report_fk')->references('id')->on('report_definitions')->restrictOnDelete();
            $table->foreign('user_id', 'rr_user_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('report_run_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 60)->index();
            $table->string('requested_format', 20);
            $table->string('format', 20);
            $table->string('scope', 60);
            $table->string('disk', 60)->default('local');
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('downloaded_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->foreign('report_run_id', 're_run_fk')->references('id')->on('report_runs')->cascadeOnDelete();
            $table->foreign('user_id', 're_user_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('report_download_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_export_id');
            $table->unsignedBigInteger('user_id');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('downloaded_at')->index();
            $table->timestamps();
            $table->foreign('report_export_id', 'rdl_export_fk')->references('id')->on('report_exports')->cascadeOnDelete();
            $table->foreign('user_id', 'rdl_user_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('report_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('report_definition_id')->nullable();
            $table->unsignedBigInteger('dashboard_definition_id')->nullable();
            $table->unsignedBigInteger('report_run_id')->nullable();
            $table->unsignedBigInteger('report_export_id')->nullable();
            $table->string('access_type', 60)->index();
            $table->string('format', 20)->nullable();
            $table->string('scope', 60)->nullable();
            $table->json('filters')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('accessed_at')->index();
            $table->timestamps();
            $table->foreign('user_id', 'ral_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('report_definition_id', 'ral_report_fk')->references('id')->on('report_definitions')->nullOnDelete();
            $table->foreign('dashboard_definition_id', 'ral_dashboard_fk')->references('id')->on('dashboard_definitions')->nullOnDelete();
            $table->foreign('report_run_id', 'ral_run_fk')->references('id')->on('report_runs')->nullOnDelete();
            $table->foreign('report_export_id', 'ral_export_fk')->references('id')->on('report_exports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_access_logs');
        Schema::dropIfExists('report_download_logs');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('report_runs');
        Schema::dropIfExists('report_filter_presets');
        Schema::dropIfExists('report_definitions');
        Schema::dropIfExists('indicator_snapshots');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboard_definitions');
        Schema::dropIfExists('indicator_definitions');
    }
};
