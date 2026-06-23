<?php

namespace App\Models;

use App\Enums\ReserveListStatus;
use Database\Factories\ReserveListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReserveList extends Model
{
    /** @use HasFactory<ReserveListFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'generated_by', 'generated_at', 'locked_at', 'locked_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ReserveListStatus::class,
            'generated_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AllocationRun, $this>
     */
    public function allocationRun(): BelongsTo
    {
        return $this->belongsTo(AllocationRun::class);
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
     * @return BelongsTo<DefinitiveList, $this>
     */
    public function definitiveList(): BelongsTo
    {
        return $this->belongsTo(DefinitiveList::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * @return HasMany<ReserveListEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ReserveListEntry::class)->orderBy('reserve_position');
    }
}
