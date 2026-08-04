<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipality_onboarding_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('operation_id')->unique();
            $table->string('municipality_code', 80)->unique();
            $table->foreignId('municipality_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('actor_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('admin_user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('status', 40)->index();
            $table->char('input_fingerprint', 64);
            $table->string('role_template_key', 120);
            $table->string('role_template_version', 40);
            $table->char('role_template_fingerprint', 64);
            $table->unsignedSmallInteger('attempt_count')->default(1);
            $table->string('failure_code', 120)->nullable();
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->timestamps();

            $table->index(
                ['status', 'started_at'],
                'municipality_onboarding_status_started_idx',
            );
        });

        Schema::create('municipal_administrator_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('onboarding_run_id')
                ->unique()
                ->constrained('municipality_onboarding_runs')
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('idempotency_key', 160)->unique();
            $table->string('status', 40)->index();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->dateTime('queued_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->dateTime('consumed_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('last_failure_code', 120)->nullable();
            $table->timestamps();

            $table->index(
                ['status', 'expires_at'],
                'municipal_admin_invitation_status_expiry_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipal_administrator_invitations');
        Schema::dropIfExists('municipality_onboarding_runs');
    }
};
