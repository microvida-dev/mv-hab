<?php

namespace App\Models;

use App\Enums\DataSubjectRequestActionType;
use Database\Factories\DataSubjectRequestActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSubjectRequestAction extends Model
{
    /** @use HasFactory<DataSubjectRequestActionFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'action_type' => DataSubjectRequestActionType::class,
            'performed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<DataSubjectRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(DataSubjectRequest::class, 'data_subject_request_id');
    }
}
