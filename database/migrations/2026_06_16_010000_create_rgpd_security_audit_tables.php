<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_number', 80)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('impersonator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_code', 150)->index();
            $table->string('event_category', 80)->index();
            $table->string('severity', 40)->index();
            $table->nullableMorphs('auditable');
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('related');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_method', 20)->nullable();
            $table->text('request_path')->nullable();
            $table->string('route_name')->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();
        });

        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('access_type', 80)->index();
            $table->nullableMorphs('resource');
            $table->string('route_name')->nullable()->index();
            $table->text('request_path')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id_hash', 128)->nullable()->index();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamp('accessed_at')->useCurrent()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('sensitive_data_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('resource');
            $table->string('sensitivity_level', 80)->default('personal')->index();
            $table->text('access_reason')->nullable();
            $table->string('action', 80)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('accessed_at')->useCurrent()->index();
            $table->timestamps();
        });

        Schema::create('permission_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('review_number', 80)->unique();
            $table->string('status', 60)->default('draft')->index();
            $table->string('scope', 150)->default('all');
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('summary')->nullable();
            $table->json('findings')->nullable();
            $table->json('recommendations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permission_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role_name')->nullable();
            $table->string('permission_name')->nullable();
            $table->string('module')->nullable()->index();
            $table->string('risk_level', 40)->default('medium')->index();
            $table->text('finding');
            $table->text('recommendation')->nullable();
            $table->text('decision')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mfa_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 60)->default('totp')->index();
            $table->string('name')->default('Aplicação autenticadora');
            $table->text('secret_encrypted');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mfa_recovery_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('consent_purposes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 150)->unique();
            $table->string('name');
            $table->text('description');
            $table->string('legal_basis', 80)->index();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('requires_explicit_consent')->default(false);
            $table->unsignedSmallInteger('retention_period_months')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consent_purpose_id')->constrained()->restrictOnDelete();
            $table->string('status', 60)->default('active')->index();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('source', 120)->default('web');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('text_snapshot');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['user_id', 'consent_purpose_id', 'status'], 'user_consents_user_purpose_status_idx');
        });

        Schema::create('retention_policies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 150)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 60)->default('draft')->index();
            $table->string('entity_type')->index();
            $table->foreignId('document_type_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('retention_period_months');
            $table->string('retention_action', 80)->index();
            $table->text('legal_basis')->nullable();
            $table->boolean('requires_manual_approval')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('retention_executions', function (Blueprint $table) {
            $table->id();
            $table->string('execution_number', 80)->unique();
            $table->foreignId('retention_policy_id')->constrained()->cascadeOnDelete();
            $table->string('status', 80)->default('simulation')->index();
            $table->string('mode', 40)->default('simulation')->index();
            $table->unsignedInteger('matched_records_count')->default(0);
            $table->unsignedInteger('affected_records_count')->default(0);
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 80)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable();
            $table->string('requester_phone', 50)->nullable();
            $table->string('request_type', 80)->index();
            $table->string('status', 80)->default('submitted')->index();
            $table->longText('description');
            $table->timestamp('identity_verified_at')->nullable();
            $table->timestamp('received_at')->nullable()->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('data_subject_request_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_subject_request_id')->constrained()->cascadeOnDelete();
            $table->string('action_type', 100)->index();
            $table->string('status', 80)->default('completed')->index();
            $table->text('description')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->nullable();
            $table->text('result_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('data_export_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_subject_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('package_number', 80)->unique();
            $table->string('status', 80)->default('generated')->index();
            $table->string('format', 40)->default('json');
            $table->string('storage_disk')->default('local');
            $table->text('storage_path');
            $table->string('filename');
            $table->string('mime_type')->default('application/json');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('checksum', 128);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('anonymization_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 80)->unique();
            $table->foreignId('data_subject_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 80)->default('draft')->index();
            $table->string('anonymization_type', 80)->default('anonymization');
            $table->text('reason');
            $table->json('scope');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->nullable();
            $table->json('summary')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('encrypted_field_registries', function (Blueprint $table) {
            $table->id();
            $table->string('model_class');
            $table->string('table_name');
            $table->string('field_name');
            $table->string('encryption_status', 80)->default('planned')->index();
            $table->string('search_strategy', 150)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('migration_required')->default(true);
            $table->timestamp('implemented_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['table_name', 'field_name'], 'encrypted_fields_table_field_unique');
        });

        Schema::create('security_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 150)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('event_code', 150)->index();
            $table->string('severity', 40)->default('medium')->index();
            $table->unsignedInteger('threshold')->nullable();
            $table->unsignedInteger('window_minutes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('security_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_number', 80)->unique();
            $table->foreignId('security_alert_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 80)->default('open')->index();
            $table->string('severity', 40)->default('medium')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('detected_at')->nullable()->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('backup_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('review_number', 80)->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->string('environment', 80)->default('local');
            $table->text('backup_scope')->nullable();
            $table->string('frequency')->nullable();
            $table->string('retention_period')->nullable();
            $table->timestamp('last_backup_at')->nullable();
            $table->timestamp('last_restore_test_at')->nullable();
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('security_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('checklist_number', 80)->unique();
            $table->string('name');
            $table->string('status', 80)->default('draft')->index();
            $table->string('environment', 80)->default('pre-production');
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('security_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_checklist_id')->constrained()->cascadeOnDelete();
            $table->string('category', 100)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 80)->default('draft')->index();
            $table->text('evidence')->nullable();
            $table->text('recommendation')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_checklist_items');
        Schema::dropIfExists('security_checklists');
        Schema::dropIfExists('backup_reviews');
        Schema::dropIfExists('security_alerts');
        Schema::dropIfExists('security_alert_rules');
        Schema::dropIfExists('encrypted_field_registries');
        Schema::dropIfExists('anonymization_requests');
        Schema::dropIfExists('data_export_packages');
        Schema::dropIfExists('data_subject_request_actions');
        Schema::dropIfExists('data_subject_requests');
        Schema::dropIfExists('retention_executions');
        Schema::dropIfExists('retention_policies');
        Schema::dropIfExists('user_consents');
        Schema::dropIfExists('consent_purposes');
        Schema::dropIfExists('mfa_recovery_codes');
        Schema::dropIfExists('mfa_devices');
        Schema::dropIfExists('permission_review_items');
        Schema::dropIfExists('permission_reviews');
        Schema::dropIfExists('sensitive_data_access_logs');
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('audit_events');
    }
};
