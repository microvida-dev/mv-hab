<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'application_review_batches',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id')->unique();
                $table->foreignId('municipality_id')
                    ->constrained()
                    ->restrictOnDelete();
                $table->foreignId('contest_id')
                    ->constrained()
                    ->restrictOnDelete();
                $table->string('cycle', 40);
                $table->unsignedInteger('sequence_number');
                $table->string('status', 32)->default('sealed');
                $table->text('reason')->nullable();
                $table->unsignedInteger('item_count')->default(0);
                $table->char('seal_key', 64)->unique();
                $table->char('source_fingerprint', 64)->unique();
                $table->char('snapshot_hash', 64);
                $table->foreignId('sealed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->dateTime('sealed_at');
                $table->timestamps();

                $table->unique(
                    ['contest_id', 'cycle'],
                    'review_batches_contest_cycle_unique',
                );
                $table->index(
                    ['municipality_id', 'status', 'sealed_at'],
                    'review_batches_municipality_status_idx',
                );
                $table->index(
                    ['contest_id', 'sequence_number'],
                    'review_batches_contest_sequence_idx',
                );
            },
        );

        Schema::create(
            'application_review_batch_items',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('application_review_batch_id');
                $table->foreign(
                    'application_review_batch_id',
                    'review_batch_items_batch_fk',
                )
                    ->references('id')
                    ->on('application_review_batches')
                    ->cascadeOnDelete();

                $table->foreignId('administrative_process_id');
                $table->foreign(
                    'administrative_process_id',
                    'review_batch_items_process_fk',
                )
                    ->references('id')
                    ->on('administrative_processes')
                    ->restrictOnDelete();

                $table->foreignId('application_id');
                $table->foreign(
                    'application_id',
                    'review_batch_items_application_fk',
                )
                    ->references('id')
                    ->on('applications')
                    ->restrictOnDelete();

                $table->foreignId('application_review_id')->nullable();
                $table->foreign(
                    'application_review_id',
                    'review_batch_items_review_fk',
                )
                    ->references('id')
                    ->on('application_reviews')
                    ->restrictOnDelete();
                $table->string('process_number');
                $table->string('application_number')->nullable();
                $table->uuid('application_public_id');
                $table->string('outcome', 64);
                $table->string('technical_result')->nullable();
                $table->unsignedInteger('review_lock_version')->nullable();
                $table->json('readiness_snapshot');
                $table->json('document_snapshot');
                $table->json('snapshot_payload');
                $table->char('source_fingerprint', 64);
                $table->char('snapshot_hash', 64);
                $table->timestamps();

                $table->unique(
                    ['application_review_batch_id', 'application_id'],
                    'review_batch_items_batch_application_unique',
                );
                $table->index(
                    ['administrative_process_id', 'outcome'],
                    'review_batch_items_process_outcome_idx',
                );
                $table->index(
                    ['application_review_id'],
                    'review_batch_items_review_idx',
                );
                $table->index(
                    ['application_public_id'],
                    'review_batch_items_application_public_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('application_review_batch_items');
        Schema::dropIfExists('application_review_batches');
    }
};
