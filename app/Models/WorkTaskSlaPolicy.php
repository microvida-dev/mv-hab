<?php

namespace App\Models;

use Database\Factories\WorkTaskSlaPolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkTaskSlaPolicy extends Model
{
    /** @use HasFactory<WorkTaskSlaPolicyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'label',
        'business_days',
        'warning_business_days',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'business_days' => 'integer',
            'warning_business_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
