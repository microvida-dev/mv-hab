<?php

namespace App\Models;

use App\Enums\ListAutomationStatus;
use App\Enums\ListAutomationType;
use Database\Factories\ListAutomationRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListAutomationRun extends Model
{
    /** @use HasFactory<ListAutomationRunFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'criteria_snapshot',
        'result_payload',
    ];

    protected function casts(): array
    {
        return [
            'type' => ListAutomationType::class,
            'status' => ListAutomationStatus::class,
            'criteria_snapshot' => 'array',
            'result_payload' => 'array',
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'run_number';
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * @return BelongsTo<RankingSnapshot, $this>
     */
    public function rankingSnapshot(): BelongsTo
    {
        return $this->belongsTo(RankingSnapshot::class, 'source_ranking_snapshot_id');
    }

    /**
     * @return BelongsTo<ProvisionalList, $this>
     */
    public function provisionalList(): BelongsTo
    {
        return $this->belongsTo(ProvisionalList::class, 'source_provisional_list_id');
    }

    /**
     * @return BelongsTo<DefinitiveList, $this>
     */
    public function definitiveList(): BelongsTo
    {
        return $this->belongsTo(DefinitiveList::class, 'source_definitive_list_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
