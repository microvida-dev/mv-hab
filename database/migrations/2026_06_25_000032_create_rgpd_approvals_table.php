<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rgpd_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approval_number', 80)->unique();
            $table->string('flow_type', 120)->index();
            $table->string('status', 80)->default('pending_dpo_approval')->index();
            $table->nullableMorphs('approvable');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('justification');
            $table->text('decision_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['flow_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rgpd_approvals');
    }
};
