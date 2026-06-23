<?php

namespace App\Models;

use App\Enums\BackupReviewStatus;
use Database\Factories\BackupReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BackupReview extends Model
{
    /** @use HasFactory<BackupReviewFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => BackupReviewStatus::class,
            'last_backup_at' => 'datetime',
            'last_restore_test_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
