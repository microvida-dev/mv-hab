<?php

namespace App\Models;

use App\Enums\AnonymizationStatus;
use Database\Factories\AnonymizationRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property AnonymizationStatus $status
 * @property array<int, string> $scope
 * @property User|null $user
 */
class AnonymizationRequest extends Model
{
    /** @use HasFactory<AnonymizationRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => AnonymizationStatus::class,
            'scope' => 'array',
            'summary' => 'array',
            'approved_at' => 'datetime',
            'executed_at' => 'datetime',
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
