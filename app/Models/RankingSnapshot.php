<?php

namespace App\Models;

use App\Enums\RankingSnapshotStatus;
use Database\Factories\RankingSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RankingSnapshot extends Model
{
    /** @use HasFactory<RankingSnapshotFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'published_at', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => RankingSnapshotStatus::class,
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ScoringRun, $this>
     */
    public function scoringRun(): BelongsTo
    {
        return $this->belongsTo(ScoringRun::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * @return HasMany<RankingEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(RankingEntry::class)->orderBy('rank_position')->orderBy('id');
    }
}
