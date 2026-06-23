<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('summary', 500);
            $table->text('description');
            $table->text('application_instructions')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('opens_at')->index();
            $table->timestamp('closes_at')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contest_deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('label');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('contest_jury_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role_in_jury');
            $table->timestamp('appointed_at')->nullable();
            $table->timestamps();

            $table->unique(['contest_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contest_jury_members');
        Schema::dropIfExists('contest_deadlines');
        Schema::dropIfExists('contests');
    }
};
