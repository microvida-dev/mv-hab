<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('municipality_id')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('code', 150);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('template_type', 60)->index();
            $table->string('channel', 60)->index();
            $table->string('status', 60)->default('draft')->index();
            $table->string('language', 10)->default('pt');
            $table->string('subject')->nullable();
            $table->string('title')->nullable();
            $table->text('body');
            $table->longText('html_body')->nullable();
            $table->text('sms_body')->nullable();
            $table->boolean('requires_acknowledgement')->default(false);
            $table->boolean('is_official')->default(false);
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('active_version_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code', 'channel'], 'notification_templates_code_channel_idx');
            $table->foreign('municipality_id', 'nt_municipality_fk')->references('id')->on('municipalities')->nullOnDelete();
            $table->foreign('program_id', 'nt_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'nt_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('created_by', 'nt_creator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'nt_updater_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('notification_template_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_template_id');
            $table->unsignedInteger('version_number');
            $table->string('status', 60)->default('draft')->index();
            $table->string('subject')->nullable();
            $table->string('title')->nullable();
            $table->text('body');
            $table->longText('html_body')->nullable();
            $table->text('sms_body')->nullable();
            $table->json('variables_schema')->nullable();
            $table->text('change_summary')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_template_id', 'version_number'], 'ntv_template_version_unique');
            $table->foreign('notification_template_id', 'ntv_template_fk')->references('id')->on('notification_templates')->cascadeOnDelete();
            $table->foreign('created_by', 'ntv_creator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'ntv_approver_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('notification_templates', function (Blueprint $table) {
            $table->foreign('active_version_id', 'nt_active_version_fk')->references('id')->on('notification_template_versions')->nullOnDelete();
        });

        Schema::create('template_variables', function (Blueprint $table) {
            $table->id();
            $table->string('code', 150)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('variable_type', 60);
            $table->string('source_key')->nullable();
            $table->text('example_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notification_event_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('municipality_id')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('event_code', 150)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->string('recipient_type', 100);
            $table->string('channel', 60);
            $table->unsignedBigInteger('notification_template_id');
            $table->boolean('requires_acknowledgement')->default(false);
            $table->string('priority', 60)->default('normal');
            $table->boolean('send_immediately')->default(true);
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('municipality_id', 'ner_municipality_fk')->references('id')->on('municipalities')->nullOnDelete();
            $table->foreign('program_id', 'ner_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'ner_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('notification_template_id', 'ner_template_fk')->references('id')->on('notification_templates')->restrictOnDelete();
            $table->foreign('created_by', 'ner_creator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'ner_updater_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->string('communication_number', 80)->unique();
            $table->string('event_code', 150)->index();
            $table->string('status', 60)->default('draft')->index();
            $table->string('priority', 60)->default('normal')->index();
            $table->unsignedBigInteger('recipient_user_id')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone', 80)->nullable();
            $table->text('recipient_address')->nullable();
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->unsignedBigInteger('notification_template_id')->nullable();
            $table->unsignedBigInteger('notification_template_version_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('title');
            $table->text('body_snapshot');
            $table->longText('html_snapshot')->nullable();
            $table->boolean('is_official')->default(false);
            $table->boolean('requires_acknowledgement')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['related_type', 'related_id'], 'communication_logs_related_idx');
            $table->foreign('recipient_user_id', 'cl_recipient_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('notification_template_id', 'cl_template_fk')->references('id')->on('notification_templates')->nullOnDelete();
            $table->foreign('notification_template_version_id', 'cl_version_fk')->references('id')->on('notification_template_versions')->nullOnDelete();
            $table->foreign('created_by', 'cl_creator_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('official_notifications', function (Blueprint $table) {
            $table->string('notification_number', 80)->nullable()->unique()->after('id');
            $table->unsignedBigInteger('communication_log_id')->nullable()->after('application_id');
            $table->string('recipient_email')->nullable()->after('user_id');
            $table->string('recipient_phone', 80)->nullable()->after('recipient_email');
            $table->string('event_code', 150)->nullable()->index()->after('notification_type');
            $table->string('priority', 60)->default('normal')->index()->after('channel');
            $table->string('title')->nullable()->after('subject');
            $table->string('action_url', 2048)->nullable()->after('body');
            $table->boolean('requires_acknowledgement')->default(false)->after('action_url');
            $table->timestamp('acknowledged_at')->nullable()->after('read_at');
            $table->timestamp('archived_at')->nullable()->after('acknowledged_at');
            $table->timestamp('cancelled_at')->nullable()->after('archived_at');
            $table->timestamp('expires_at')->nullable()->after('cancelled_at');
            $table->foreign('communication_log_id', 'on_communication_fk')->references('id')->on('communication_logs')->nullOnDelete();
        });

        DB::table('official_notifications')->orderBy('id')->get(['id'])->each(function (object $row): void {
            DB::table('official_notifications')->where('id', $row->id)->update([
                'notification_number' => 'NOT-'.str_pad((string) $row->id, 10, '0', STR_PAD_LEFT),
            ]);
        });

        Schema::create('communication_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('communication_log_id');
            $table->unsignedBigInteger('official_notification_id')->nullable();
            $table->string('channel', 60)->index();
            $table->string('status', 60)->default('pending')->index();
            $table->text('destination')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->text('provider_response')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('postal_reference')->nullable();
            $table->text('postal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('communication_log_id', 'cd_log_fk')->references('id')->on('communication_logs')->cascadeOnDelete();
            $table->foreign('official_notification_id', 'cd_notification_fk')->references('id')->on('official_notifications')->nullOnDelete();
        });

        Schema::create('communication_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('communication_delivery_id');
            $table->unsignedInteger('attempt_number');
            $table->string('status', 60)->index();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('provider')->nullable();
            $table->text('request_payload_summary')->nullable();
            $table->text('response_payload_summary')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['communication_delivery_id', 'attempt_number'], 'ca_delivery_attempt_unique');
            $table->foreign('communication_delivery_id', 'ca_delivery_fk')->references('id')->on('communication_deliveries')->cascadeOnDelete();
            $table->foreign('created_by', 'ca_creator_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('communication_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('communication_log_id');
            $table->unsignedBigInteger('communication_delivery_id')->nullable();
            $table->string('receipt_number', 80)->unique();
            $table->string('receipt_type', 80)->index();
            $table->string('storage_disk', 80)->default('local');
            $table->string('storage_path');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size');
            $table->string('checksum', 128);
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('communication_log_id', 'cr_log_fk')->references('id')->on('communication_logs')->cascadeOnDelete();
            $table->foreign('communication_delivery_id', 'cr_delivery_fk')->references('id')->on('communication_deliveries')->nullOnDelete();
            $table->foreign('generated_by', 'cr_generator_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('allow_in_app')->default(true);
            $table->boolean('allow_email')->default(true);
            $table->boolean('allow_sms')->default(false);
            $table->boolean('allow_postal')->default(true);
            $table->string('preferred_channel', 60)->nullable();
            $table->string('email_for_notifications')->nullable();
            $table->string('phone_for_notifications', 80)->nullable();
            $table->text('postal_address')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'np_user_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('municipality_id')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('code', 150);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 150)->index();
            $table->string('status', 60)->default('draft')->index();
            $table->string('language', 10)->default('pt');
            $table->string('title');
            $table->longText('body');
            $table->longText('html_body')->nullable();
            $table->longText('footer')->nullable();
            $table->longText('header')->nullable();
            $table->boolean('is_official')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('requires_approval')->default(true);
            $table->unsignedBigInteger('active_version_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code', 'document_templates_code_idx');
            $table->foreign('municipality_id', 'dt_municipality_fk')->references('id')->on('municipalities')->nullOnDelete();
            $table->foreign('program_id', 'dt_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'dt_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('created_by', 'dt_creator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'dt_updater_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('document_template_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_template_id');
            $table->unsignedInteger('version_number');
            $table->string('status', 60)->default('draft')->index();
            $table->string('title');
            $table->longText('body');
            $table->longText('html_body')->nullable();
            $table->longText('header')->nullable();
            $table->longText('footer')->nullable();
            $table->json('variables_schema')->nullable();
            $table->text('change_summary')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['document_template_id', 'version_number'], 'dtv_template_version_unique');
            $table->foreign('document_template_id', 'dtv_template_fk')->references('id')->on('document_templates')->cascadeOnDelete();
            $table->foreign('created_by', 'dtv_creator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'dtv_approver_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('document_templates', function (Blueprint $table) {
            $table->foreign('active_version_id', 'dt_active_version_fk')->references('id')->on('document_template_versions')->nullOnDelete();
        });

        Schema::create('generated_official_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 80)->unique();
            $table->unsignedBigInteger('document_template_id');
            $table->unsignedBigInteger('document_template_version_id');
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->unsignedBigInteger('recipient_user_id')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('status', 60)->default('draft')->index();
            $table->string('title');
            $table->longText('html_content');
            $table->string('storage_disk', 80)->default('local');
            $table->string('storage_path');
            $table->string('mime_type', 150)->default('text/html');
            $table->unsignedBigInteger('file_size');
            $table->string('checksum', 128);
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at');
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['related_type', 'related_id'], 'god_related_idx');
            $table->foreign('document_template_id', 'god_template_fk')->references('id')->on('document_templates')->restrictOnDelete();
            $table->foreign('document_template_version_id', 'god_version_fk')->references('id')->on('document_template_versions')->restrictOnDelete();
            $table->foreign('recipient_user_id', 'god_recipient_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('generated_by', 'god_generator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('issued_by', 'god_issuer_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by', 'god_canceller_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_official_documents');
        Schema::table('document_templates', fn (Blueprint $table) => $table->dropForeign('dt_active_version_fk'));
        Schema::dropIfExists('document_template_versions');
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('communication_receipts');
        Schema::dropIfExists('communication_attempts');
        Schema::dropIfExists('communication_deliveries');

        Schema::table('official_notifications', function (Blueprint $table) {
            $table->dropForeign('on_communication_fk');
            $table->dropColumn([
                'notification_number',
                'communication_log_id',
                'recipient_email',
                'recipient_phone',
                'event_code',
                'priority',
                'title',
                'action_url',
                'requires_acknowledgement',
                'acknowledged_at',
                'archived_at',
                'cancelled_at',
                'expires_at',
            ]);
        });

        Schema::dropIfExists('communication_logs');
        Schema::dropIfExists('notification_event_rules');
        Schema::dropIfExists('template_variables');
        Schema::table('notification_templates', fn (Blueprint $table) => $table->dropForeign('nt_active_version_fk'));
        Schema::dropIfExists('notification_template_versions');
        Schema::dropIfExists('notification_templates');
    }
};
