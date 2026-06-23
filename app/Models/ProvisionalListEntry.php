<?php

namespace App\Models;

use App\Enums\ListEntryStatus;
use App\Enums\ListEntryType;
use Database\Factories\ProvisionalListEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ListEntryStatus $status
 * @property ListEntryType $entry_type
 */
class ProvisionalListEntry extends Model
{
    /** @use HasFactory<ProvisionalListEntryFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'entry_type', 'rank_position', 'total_score', 'public_identifier', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'entry_type' => ListEntryType::class,
            'status' => ListEntryStatus::class,
            'total_score' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<ProvisionalList, $this> */
    public function provisionalList(): BelongsTo
    {
        return $this->belongsTo(ProvisionalList::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<ApplicationScore, $this> */
    public function applicationScore(): BelongsTo
    {
        return $this->belongsTo(ApplicationScore::class);
    }

    /** @return BelongsTo<RankingEntry, $this> */
    public function rankingEntry(): BelongsTo
    {
        return $this->belongsTo(RankingEntry::class);
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<Complaint, $this> */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /** @return HasOne<DefinitiveListEntry, $this> */
    public function definitiveListEntry(): HasOne
    {
        return $this->hasOne(DefinitiveListEntry::class);
    }
}
