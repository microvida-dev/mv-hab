<?php

namespace App\Models;

use Database\Factories\DataExportPackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataExportPackage extends Model
{
    /** @use HasFactory<DataExportPackageFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'downloaded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DataSubjectRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(DataSubjectRequest::class, 'data_subject_request_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
