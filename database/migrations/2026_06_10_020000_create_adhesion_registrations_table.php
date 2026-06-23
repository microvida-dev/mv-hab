<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adhesion_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('status')->default('incomplete')->index();

            $table->string('full_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile_phone', 50)->nullable();
            $table->string('document_type', 50)->nullable();
            $table->string('document_number', 100)->nullable();
            $table->date('document_valid_until')->nullable();
            $table->string('nif', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality', 100)->nullable();

            $table->string('address')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('parish', 100)->nullable();
            $table->string('municipality', 100)->nullable();

            $table->boolean('wants_email_notifications')->default(true);
            $table->boolean('wants_sms_notifications')->default(false);
            $table->boolean('wants_postal_notifications')->default(false);

            $table->boolean('accepts_terms')->default(false);
            $table->boolean('accepts_data_processing')->default(false);
            $table->timestamp('accepted_terms_at')->nullable();
            $table->timestamp('accepted_data_processing_at')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('adhesion_registration_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('adhesion_registration_id');
            $table->string('from_status')->nullable();
            $table->string('to_status')->index();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();

            $table->foreign('adhesion_registration_id', 'adhesion_history_registration_fk')
                ->references('id')
                ->on('adhesion_registrations')
                ->cascadeOnDelete();
            $table->foreign('changed_by', 'adhesion_history_changed_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adhesion_registration_status_histories');
        Schema::dropIfExists('adhesion_registrations');
    }
};
