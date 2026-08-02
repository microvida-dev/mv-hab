<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'correction_requests',
            function (Blueprint $table): void {
                $table->timestamp('original_response_deadline_at')
                    ->nullable()
                    ->after('response_deadline_at');
                $table->unsignedSmallInteger(
                    'deadline_extension_count',
                )->default(0)
                    ->after('original_response_deadline_at');
            },
        );

        DB::table('correction_requests')
            ->whereNotNull('response_deadline_at')
            ->whereNull('original_response_deadline_at')
            ->update([
                'original_response_deadline_at' => DB::raw('response_deadline_at'),
            ]);

        Schema::create(
            'correction_deadline_extensions',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('correction_request_id')
                    ->constrained('correction_requests')
                    ->cascadeOnDelete();
                $table->dateTime('original_deadline_at');
                $table->dateTime('previous_deadline_at');
                $table->dateTime('extended_deadline_at');
                $table->text('reason');
                $table->foreignId('authorized_by')
                    ->constrained('users')
                    ->restrictOnDelete();
                $table->dateTime('authorized_at');
                $table->timestamps();

                $table->unique(
                    [
                        'correction_request_id',
                        'extended_deadline_at',
                    ],
                    'corr_deadline_ext_request_deadline_uq',
                );
                $table->index(
                    [
                        'correction_request_id',
                        'authorized_at',
                    ],
                    'corr_deadline_ext_request_authorized_idx',
                );
            },
        );

        Schema::create(
            'correction_submission_receipts',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('correction_request_id')
                    ->unique()
                    ->constrained('correction_requests')
                    ->restrictOnDelete();
                $table->foreignId('application_id')
                    ->constrained('applications')
                    ->restrictOnDelete();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->restrictOnDelete();
                $table->foreignId('municipal_notification_id')
                    ->nullable()
                    ->unique()
                    ->constrained('official_notifications')
                    ->nullOnDelete();
                $table->string('receipt_number')->unique();
                $table->json('snapshot_payload');
                $table->char('snapshot_hash', 64)->unique();
                $table->dateTime('submitted_at');
                $table->dateTime('created_at');

                $table->index(
                    ['user_id', 'submitted_at'],
                    'corr_receipts_user_submitted_idx',
                );
                $table->index(
                    ['application_id', 'submitted_at'],
                    'corr_receipts_application_submitted_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'correction_submission_receipts',
        );
        Schema::dropIfExists(
            'correction_deadline_extensions',
        );

        Schema::table(
            'correction_requests',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'original_response_deadline_at',
                    'deadline_extension_count',
                ]);
            },
        );
    }
};
