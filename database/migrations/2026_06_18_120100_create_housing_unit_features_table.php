<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_unit_features', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('housing_unit_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('value')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->unique(['housing_unit_id', 'key']);
            $table->index(['housing_unit_id', 'is_public', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_unit_features');
    }
};
