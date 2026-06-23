<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_portal_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('type')->default('string');
            $table->json('value')->nullable();
            $table->string('label')->nullable();
            $table->string('description', 500)->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_portal_settings');
    }
};
