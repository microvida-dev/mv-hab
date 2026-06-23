<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_unit_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('housing_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->boolean('is_cover')->default(false);
            $table->boolean('is_public')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['housing_unit_id', 'is_public', 'sort_order']);
            $table->index(['housing_unit_id', 'is_cover']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_unit_images');
    }
};
