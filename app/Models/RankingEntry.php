<?php

namespace App\Models;

use App\Enums\RankingEntryStatus;
use Database\Factories\RankingEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property RankingEntryStatus $status
 * @property bool $is_tied
 */
class RankingEntry extends Model
{
    /** @use HasFactory<RankingEntryFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'total_score' => 'decimal:2',
            'tie_breaker_values' => 'array',
            'is_tied' => 'boolean',
            'status' => RankingEntryStatus::class,
        ];
    }

    /**
     * @return BelongsTo<RankingSnapshot, $this>
     */
    public function rankingSnapshot(): BelongsTo
    {
        return $this->belongsTo(RankingSnapshot::class);
    }

    /**
     * @return BelongsTo<ApplicationScore, $this>
     */
    public function applicationScore(): BelongsTo
    {
        return $this->belongsTo(ApplicationScore::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
