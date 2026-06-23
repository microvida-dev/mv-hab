<?php

namespace App\Models;

use Database\Factories\EligibilitySnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EligibilitySnapshot extends Model
{
    /** @use HasFactory<EligibilitySnapshotFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<EligibilityCheck, $this>
     */
    public function check(): BelongsTo
    {
        return $this->belongsTo(EligibilityCheck::class, 'eligibility_check_id');
    }
}
