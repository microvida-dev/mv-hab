<?php

namespace App\Models;

use App\Enums\ContestDeadlineType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContestDeadline extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    protected $fillable = [
        'contest_id',
        'type',
        'label',
        'starts_at',
        'ends_at',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => ContestDeadlineType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }
}
