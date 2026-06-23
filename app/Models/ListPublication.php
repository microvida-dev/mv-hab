<?php

namespace App\Models;

use App\Enums\AnonymizationMode;
use App\Enums\ListPublicationChannel;
use App\Enums\ListPublicationStatus;
use App\Enums\ListPublicationType;
use Database\Factories\ListPublicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListPublication extends Model
{
    /** @use HasFactory<ListPublicationFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'published_by', 'published_at', 'unpublished_by', 'unpublished_at', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'publication_type' => ListPublicationType::class,
            'status' => ListPublicationStatus::class,
            'channel' => ListPublicationChannel::class,
            'anonymization_mode' => AnonymizationMode::class,
            'published_at' => 'datetime',
            'unpublished_at' => 'datetime',
            'visibility_starts_at' => 'datetime',
            'visibility_ends_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function publishable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function unpublishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unpublished_by');
    }

    /**
     * @param  Builder<ListPublication>  $query
     * @return Builder<ListPublication>
     */
    public function scopePublicPortal(Builder $query): Builder
    {
        return $query->where('channel', ListPublicationChannel::PublicPortal->value);
    }

    /**
     * @param  Builder<ListPublication>  $query
     * @return Builder<ListPublication>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', ListPublicationStatus::Published->value)
            ->where(fn (Builder $builder) => $builder->whereNull('visibility_starts_at')->orWhere('visibility_starts_at', '<=', now()))
            ->where(fn (Builder $builder) => $builder->whereNull('visibility_ends_at')->orWhere('visibility_ends_at', '>=', now()));
    }
}
