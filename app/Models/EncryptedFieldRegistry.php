<?php

namespace App\Models;

use App\Enums\EncryptedFieldStatus;
use Database\Factories\EncryptedFieldRegistryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EncryptedFieldRegistry extends Model
{
    /** @use HasFactory<EncryptedFieldRegistryFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'encryption_status' => EncryptedFieldStatus::class,
            'migration_required' => 'boolean',
            'implemented_at' => 'datetime',
        ];
    }
}
