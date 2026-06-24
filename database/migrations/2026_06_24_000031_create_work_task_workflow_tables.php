<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_task_sla_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 80)->unique();
            $table->string('label', 160);
            $table->unsignedSmallInteger('business_days');
            $table->unsignedSmallInteger('warning_business_days')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('work_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('task_number', 40)->unique();
            $table->string('type', 80)->index();
            $table->string('source', 120)->nullable()->index();
            $table->nullableMorphs('related');
            $table->string('priority', 40)->default('normal')->index();
            $table->string('status', 40)->default('pending')->index();
            $table->foreignId('municipal_team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('reassignment_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('outcome_note')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'due_at'], 'work_tasks_status_due_idx');
            $table->index(['type', 'status'], 'work_tasks_type_status_idx');
            $table->index(['municipal_team_id', 'status'], 'work_tasks_team_status_idx');
            $table->index(['assigned_user_id', 'status'], 'work_tasks_assignee_status_idx');
            $table->unique(
                ['type', 'source', 'related_type', 'related_id', 'completed_at', 'cancelled_at'],
                'work_tasks_active_source_unique',
            );
        });

        Schema::create('work_task_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_task_id')->constrained()->cascadeOnDelete();
            $table->string('event_code', 120)->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->foreignId('from_team_id')->nullable()->constrained('municipal_teams')->nullOnDelete();
            $table->foreignId('to_team_id')->nullable()->constrained('municipal_teams')->nullOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['work_task_id', 'event_code'], 'work_task_histories_task_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_task_histories');
        Schema::dropIfExists('work_tasks');
        Schema::dropIfExists('work_task_sla_policies');
    }
};
