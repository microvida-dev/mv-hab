<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'item_type',
    'workspace_key',
    'label',
    'route_name',
    'route_parameters',
    'resource_type',
    'resource_id',
    'metadata',
    'last_visited_at',
    'visits_count',
])]
class NavigationRecentItem extends Model
{
    protected function casts(): array
    {
        return [
            'route_parameters' => 'array',
            'metadata' => 'array',
            'last_visited_at' => 'datetime',
            'visits_count' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
