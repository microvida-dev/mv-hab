<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_operator_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('status', 40)->index();
            $table->string('grant_source', 40)->index();
            $table->foreignId('granted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('granted_at');
            $table->text('grant_justification');
            $table->string('approval_reference_primary', 160)->nullable();
            $table->string('approval_reference_secondary', 160)->nullable();
            $table->foreignId('revoked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revoke_justification')->nullable();
            $table->timestamps();

            $table->unique('user_id', 'poa_user_unique');
            $table->index(['status', 'revoked_at'], 'poa_status_revoked_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_operator_assignments');
    }
};
