<?php

namespace Database\Factories;

use App\Enums\BackupReviewStatus;
use App\Models\BackupReview;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<BackupReview> */
class BackupReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'review_number' => 'BKP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'status' => BackupReviewStatus::Reviewed->value,
            'environment' => 'test',
            'backup_scope' => 'Backup demo.',
            'reviewed_at' => now(),
        ];
    }
}
