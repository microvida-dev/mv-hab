<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'citizen_id',
        'housing_application_id',
        'contract_id',
        'name',
        'path',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /** @return BelongsTo<Citizen, $this> */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    /** @return BelongsTo<HousingApplication, $this> */
    public function housingApplication(): BelongsTo
    {
        return $this->belongsTo(HousingApplication::class);
    }

    /** @return BelongsTo<Contract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
