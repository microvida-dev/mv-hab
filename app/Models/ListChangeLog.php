<?php

namespace App\Models;

use App\Enums\ListChangeType;
use Database\Factories\ListChangeLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ListChangeLog extends Model
{
    /** @use HasFactory<ListChangeLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = ['id', 'created_at'];

    protected function casts(): array
    {
        return [
            'change_type' => ListChangeType::class,
        ];
    }

    /**
     * @return BelongsTo<ProvisionalList, $this>
     */
    public function provisionalList(): BelongsTo
    {
        return $this->belongsTo(ProvisionalList::class);
    }

    /**
     * @return BelongsTo<DefinitiveList, $this>
     */
    public function definitiveList(): BelongsTo
    {
        return $this->belongsTo(DefinitiveList::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
