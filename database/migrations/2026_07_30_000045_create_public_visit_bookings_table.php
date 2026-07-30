<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'public_visit_bookings',
            function (Blueprint $table): void {
                $table->id();
                $table->string('booking_reference', 80)->unique();
                $table
                    ->foreignId('municipality_id')
                    ->constrained('municipalities')
                    ->restrictOnDelete();
                $table
                    ->foreignId('visit_slot_id')
                    ->constrained('visit_slots')
                    ->restrictOnDelete();
                $table
                    ->foreignId('housing_unit_id')
                    ->constrained('housing_units')
                    ->restrictOnDelete();
                $table
                    ->foreignId('contest_id')
                    ->nullable()
                    ->constrained('contests')
                    ->nullOnDelete();
                $table->string('status', 40)->default('booked');
                $table->text('contact_name')->nullable();
                $table->text('contact_email')->nullable();
                $table->text('contact_phone')->nullable();
                $table->char('email_hash', 64)->index();
                $table
                    ->char('active_fingerprint', 64)
                    ->nullable()
                    ->unique();
                $table->unsignedSmallInteger('guest_count')->default(1);
                $table->char('cancellation_token_hash', 64)->unique();
                $table->text('cancellation_token')->nullable();
                $table->timestamp('cancellation_token_expires_at');
                $table->timestamp('privacy_notice_accepted_at');
                $table->string('privacy_notice_version', 80);
                $table->string('booking_source', 80)->default('public_portal');
                $table->timestamp('booked_at');
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('confirmation_sent_at')->nullable();
                $table->timestamp('confirmation_failed_at')->nullable();
                $table->string('confirmation_error_code', 160)->nullable();
                $table->timestamp('retention_due_at')->index();
                $table->timestamp('anonymized_at')->nullable()->index();
                $table->text('status_notes')->nullable();
                $table
                    ->foreignId('status_changed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamps();

                $table->index(
                    ['municipality_id', 'status', 'booked_at'],
                    'public_visit_bookings_municipal_status_idx',
                );
                $table->index(
                    ['visit_slot_id', 'status'],
                    'public_visit_bookings_slot_status_idx',
                );
                $table->index(
                    ['housing_unit_id', 'status'],
                    'public_visit_bookings_housing_status_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('public_visit_bookings');
    }
};
