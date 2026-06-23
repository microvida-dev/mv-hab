<?php

namespace App\Models;

use Database\Factories\IncomeSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomeSource extends Model
{
    /** @use HasFactory<IncomeSourceFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<IncomeRecord, $this> */
    public function incomeRecords(): HasMany
    {
        return $this->hasMany(IncomeRecord::class);
    }
}
