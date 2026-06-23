<?php

namespace App\Models;

use Database\Factories\PublicPortalSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicPortalSetting extends Model
{
    /** @use HasFactory<PublicPortalSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'type',
        'value',
        'label',
        'description',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_public' => 'boolean',
        ];
    }
}
