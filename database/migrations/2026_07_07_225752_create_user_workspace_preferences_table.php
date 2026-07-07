<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_workspace_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('preferred_workspace')->nullable();
            $table->json('collapsed_groups')->nullable();
            $table->json('hidden_modules')->nullable();
            $table->json('dashboard_layout')->nullable();
            $table->json('workspace_layout')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('preferred_workspace');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_workspace_preferences');
    }
};
