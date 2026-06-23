<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_availabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('housing_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('slot_duration_minutes')->default(30);
            $table->unsignedSmallInteger('capacity_per_slot')->default(1);
            $table->unsignedSmallInteger('buffer_minutes')->default(0);
            $table->string('timezone')->default(config('app.timezone', 'UTC'));
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('contest_id', 'visit_availabilities_contest_idx');
            $table->index('housing_unit_id', 'visit_availabilities_housing_unit_idx');
            $table->index('staff_user_id', 'visit_availabilities_staff_idx');
            $table->index('starts_at', 'visit_availabilities_starts_at_idx');
            $table->index('ends_at', 'visit_availabilities_ends_at_idx');
            $table->index('is_active', 'visit_availabilities_active_idx');
        });

        Schema::create('visit_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('visit_availability_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('housing_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 50)->default('available');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->string('location')->nullable();
            $table->string('meeting_point')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['visit_availability_id', 'starts_at', 'ends_at'], 'visit_slots_unique_window');
            $table->index('visit_availability_id', 'visit_slots_availability_idx');
            $table->index('contest_id', 'visit_slots_contest_idx');
            $table->index('housing_unit_id', 'visit_slots_housing_unit_idx');
            $table->index('staff_user_id', 'visit_slots_staff_idx');
            $table->index('starts_at', 'visit_slots_starts_at_idx');
            $table->index('status', 'visit_slots_status_idx');
        });

        Schema::create('housing_visits', function (Blueprint $table): void {
            $table->id();
            $table->string('visit_number')->unique();
            $table->foreignId('visit_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('housing_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('candidate_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('requested');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason', 100)->nullable();
            $table->text('cancellation_notes')->nullable();
            $table->foreignId('rescheduled_from_id')->nullable()->constrained('housing_visits')->nullOnDelete();
            $table->text('candidate_notes')->nullable();
            $table->text('staff_notes')->nullable();
            $table->string('location')->nullable();
            $table->string('meeting_point')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('candidate_user_id', 'housing_visits_candidate_idx');
            $table->index('application_id', 'housing_visits_application_idx');
            $table->index('contest_id', 'housing_visits_contest_idx');
            $table->index('housing_unit_id', 'housing_visits_housing_unit_idx');
            $table->index('status', 'housing_visits_status_idx');
            $table->index('starts_at', 'housing_visits_starts_at_idx');
            $table->index(['candidate_user_id', 'visit_slot_id'], 'housing_visits_candidate_slot_idx');
        });

        Schema::create('housing_visit_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('housing_visit_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('changed_at');
            $table->timestamp('created_at')->nullable();

            $table->index('housing_visit_id', 'housing_visit_history_visit_idx');
        });

        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('housing_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 100);
            $table->string('priority', 50)->default('normal');
            $table->string('status', 50)->default('open');
            $table->string('subject', 180);
            $table->text('description');
            $table->json('context')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id', 'support_tickets_user_idx');
            $table->index('application_id', 'support_tickets_application_idx');
            $table->index('status', 'support_tickets_status_idx');
            $table->index('category', 'support_tickets_category_idx');
            $table->index('priority', 'support_tickets_priority_idx');
            $table->index('assigned_to', 'support_tickets_assigned_to_idx');
            $table->index('last_message_at', 'support_tickets_last_message_at_idx');
        });

        Schema::create('support_ticket_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visibility', 50)->default('candidate_visible');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->dateTime('read_by_candidate_at')->nullable();
            $table->dateTime('read_by_staff_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('support_ticket_id', 'support_ticket_messages_ticket_idx');
            $table->index('sender_user_id', 'support_ticket_messages_sender_idx');
            $table->index('created_at', 'support_ticket_messages_created_at_idx');
        });

        Schema::create('support_ticket_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('support_ticket_message_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('storage_disk')->default('local');
            $table->string('path');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum', 128);
            $table->boolean('is_private')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('support_ticket_id', 'support_ticket_attachments_ticket_idx');
            $table->index('support_ticket_message_id', 'support_ticket_attachments_message_idx');
            $table->index('uploaded_by', 'support_ticket_attachments_uploaded_by_idx');
        });

        Schema::create('candidate_interactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('housing_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('interaction_type', 100);
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index('user_id', 'candidate_interactions_user_idx');
            $table->index('application_id', 'candidate_interactions_application_idx');
            $table->index('interaction_type', 'candidate_interactions_type_idx');
            $table->index('occurred_at', 'candidate_interactions_occurred_at_idx');
            $table->index(['related_type', 'related_id'], 'candidate_interactions_related_idx');
        });

        Schema::create('contextual_faq_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contextual_faqs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contextual_faq_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('context_key', 100);
            $table->string('question');
            $table->text('answer');
            $table->json('keywords')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->dateTime('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('context_key', 'contextual_faqs_context_key_idx');
            $table->index('contest_id', 'contextual_faqs_contest_idx');
            $table->index('is_active', 'contextual_faqs_active_idx');
        });

        Schema::create('application_simulation_inconsistencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id');
            $table->foreignId('simulation_session_id')->nullable();
            $table->foreignId('user_id');
            $table->string('type', 100);
            $table->string('severity', 50);
            $table->string('field_name')->nullable();
            $table->json('simulation_value')->nullable();
            $table->json('application_value')->nullable();
            $table->string('message');
            $table->text('recommendation')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('application_id', 'app_sim_incons_application_fk')->references('id')->on('applications')->cascadeOnDelete();
            $table->foreign('simulation_session_id', 'app_sim_incons_session_fk')->references('id')->on('simulation_sessions')->nullOnDelete();
            $table->foreign('user_id', 'app_sim_incons_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('resolved_by', 'app_sim_incons_resolved_by_fk')->references('id')->on('users')->nullOnDelete();

            $table->index('application_id', 'application_sim_incons_application_idx');
            $table->index('user_id', 'application_sim_incons_user_idx');
            $table->index('type', 'application_sim_incons_type_idx');
            $table->index('severity', 'application_sim_incons_severity_idx');
            $table->index('is_resolved', 'application_sim_incons_resolved_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_simulation_inconsistencies');
        Schema::dropIfExists('contextual_faqs');
        Schema::dropIfExists('contextual_faq_categories');
        Schema::dropIfExists('candidate_interactions');
        Schema::dropIfExists('support_ticket_attachments');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('housing_visit_status_histories');
        Schema::dropIfExists('housing_visits');
        Schema::dropIfExists('visit_slots');
        Schema::dropIfExists('visit_availabilities');
    }
};
