<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 32);
            $table->string('workspace_key')->nullable()->index();
            $table->string('label');
            $table->string('route_name')->nullable();
            $table->json('route_parameters')->nullable();
            $table->string('resource_type')->nullable();
            $table->string('resource_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'item_type']);
            $table->index(['route_name', 'resource_type']);
        });

        Schema::create('navigation_recent_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 32);
            $table->string('workspace_key')->nullable()->index();
            $table->string('label');
            $table->string('route_name')->nullable();
            $table->json('route_parameters')->nullable();
            $table->string('resource_type')->nullable();
            $table->string('resource_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_visited_at')->nullable()->index();
            $table->unsignedInteger('visits_count')->default(1);
            $table->timestamps();

            $table->index(['user_id', 'item_type']);
            $table->index(['route_name', 'resource_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_recent_items');
        Schema::dropIfExists('navigation_favorites');
    }
};
