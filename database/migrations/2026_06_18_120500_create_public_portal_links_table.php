<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_portal_links', function (Blueprint $table): void {
            $table->id();
            $table->string('label');
            $table->string('url');
            $table->string('category')->default('institutional')->index();
            $table->string('description', 500)->nullable();
            $table->boolean('opens_new_tab')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_portal_links');
    }
};
