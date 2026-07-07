<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'preferred_workspace',
    'collapsed_groups',
    'hidden_modules',
    'dashboard_layout',
    'workspace_layout',
    'settings',
])]
class UserWorkspacePreference extends Model
{
    protected function casts(): array
    {
        return [
            'collapsed_groups' => 'array',
            'hidden_modules' => 'array',
            'dashboard_layout' => 'array',
            'workspace_layout' => 'array',
            'settings' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
