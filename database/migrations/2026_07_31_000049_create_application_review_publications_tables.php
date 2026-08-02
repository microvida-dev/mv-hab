<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'application_review_publications',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id');
                $table->unique(
                    'public_id',
                    'review_publications_public_id_uq',
                );
                $table->foreignId('municipality_id');
                $table->foreign(
                    'municipality_id',
                    'review_publications_municipality_fk',
                )->references('id')->on('municipalities')->restrictOnDelete();
                $table->foreignId('contest_id');
                $table->foreign(
                    'contest_id',
                    'review_publications_contest_fk',
                )->references('id')->on('contests')->restrictOnDelete();
                $table->foreignId('application_review_batch_id');
                $table->unique(
                    'application_review_batch_id',
                    'review_publications_batch_uq',
                );
                $table->foreign(
                    'application_review_batch_id',
                    'review_publications_batch_fk',
                )->references('id')->on('application_review_batches')->restrictOnDelete();
                $table->string('cycle', 40);
                $table->unsignedInteger('sequence_number');
                $table->string('status', 32)->default('published');
                $table->text('reason');
                $table->unsignedInteger('item_count');
                $table->char('publication_key', 64);
                $table->unique(
                    'publication_key',
                    'review_publications_key_uq',
                );
                $table->char('source_snapshot_hash', 64);
                $table->char('publication_hash', 64);
                $table->unique(
                    'publication_hash',
                    'review_publications_hash_uq',
                );
                $table->foreignId('published_by')->nullable();
                $table->foreign(
                    'published_by',
                    'review_publications_publisher_fk',
                )->references('id')->on('users')->nullOnDelete();
                $table->dateTime('published_at');
                $table->timestamps();

                $table->index(
                    ['municipality_id', 'published_at'],
                    'review_publications_municipality_published_idx',
                );
                $table->index(
                    ['contest_id', 'cycle', 'sequence_number'],
                    'review_publications_contest_cycle_idx',
                );
            },
        );

        Schema::create(
            'application_review_publication_results',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id');
                $table->unique(
                    'public_id',
                    'review_results_public_id_uq',
                );
                $table->foreignId('application_review_publication_id');
                $table->foreign(
                    'application_review_publication_id',
                    'review_results_publication_fk',
                )->references('id')->on('application_review_publications')->cascadeOnDelete();
                $table->foreignId('application_review_batch_item_id');
                $table->unique(
                    'application_review_batch_item_id',
                    'review_results_batch_item_uq',
                );
                $table->foreign(
                    'application_review_batch_item_id',
                    'review_results_batch_item_fk',
                )->references('id')->on('application_review_batch_items')->restrictOnDelete();
                $table->foreignId('municipality_id');
                $table->foreign(
                    'municipality_id',
                    'review_results_municipality_fk',
                )->references('id')->on('municipalities')->restrictOnDelete();
                $table->foreignId('contest_id');
                $table->foreign(
                    'contest_id',
                    'review_results_contest_fk',
                )->references('id')->on('contests')->restrictOnDelete();
                $table->foreignId('administrative_process_id');
                $table->foreign(
                    'administrative_process_id',
                    'review_results_process_fk',
                )->references('id')->on('administrative_processes')->restrictOnDelete();
                $table->foreignId('application_id');
                $table->foreign(
                    'application_id',
                    'review_results_application_fk',
                )->references('id')->on('applications')->restrictOnDelete();
                $table->foreignId('user_id');
                $table->foreign(
                    'user_id',
                    'review_results_user_fk',
                )->references('id')->on('users')->restrictOnDelete();
                $table->string('process_number');
                $table->string('application_number')->nullable();
                $table->uuid('application_public_id');
                $table->string('outcome', 64);
                $table->string('technical_result')->nullable();
                $table->json('result_payload');
                $table->char('source_snapshot_hash', 64);
                $table->char('result_hash', 64);
                $table->char('notification_hash', 64);
                $table->foreignId('official_notification_id');
                $table->unique(
                    'official_notification_id',
                    'review_results_notification_uq',
                );
                $table->foreign(
                    'official_notification_id',
                    'review_results_notification_fk',
                )->references('id')->on('official_notifications')->restrictOnDelete();
                $table->foreignId('communication_log_id');
                $table->unique(
                    'communication_log_id',
                    'review_results_communication_uq',
                );
                $table->foreign(
                    'communication_log_id',
                    'review_results_communication_fk',
                )->references('id')->on('communication_logs')->restrictOnDelete();
                $table->foreignId('in_app_delivery_id');
                $table->unique(
                    'in_app_delivery_id',
                    'review_results_in_app_delivery_uq',
                );
                $table->foreign(
                    'in_app_delivery_id',
                    'review_results_in_app_delivery_fk',
                )->references('id')->on('communication_deliveries')->restrictOnDelete();
                $table->foreignId('email_delivery_id');
                $table->unique(
                    'email_delivery_id',
                    'review_results_email_delivery_uq',
                );
                $table->foreign(
                    'email_delivery_id',
                    'review_results_email_delivery_fk',
                )->references('id')->on('communication_deliveries')->restrictOnDelete();
                $table->dateTime('published_at');
                $table->timestamps();

                $table->unique(
                    ['application_review_publication_id', 'application_id'],
                    'review_results_publication_application_unique',
                );
                $table->index(
                    ['user_id', 'published_at'],
                    'review_results_user_published_idx',
                );
                $table->index(
                    ['application_id', 'published_at'],
                    'review_results_application_published_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('application_review_publication_results');
        Schema::dropIfExists('application_review_publications');
    }
};
