<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('housing_unit_public_documents')) {
            return;
        }

        Schema::create('housing_unit_public_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('housing_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('description', 500)->nullable();
            $table->string('document_type')->default('other')->index();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum', 128)->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('download_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['housing_unit_id', 'is_public', 'sort_order'], 'hupd_unit_public_sort_idx');
            $table->index(['contest_id', 'is_public'], 'hupd_contest_public_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_unit_public_documents');
    }
};
