<?php

namespace App\Models;

use Database\Factories\ApplicationPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationPreference extends Model
{
    /** @use HasFactory<ApplicationPreferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'housing_unit_id',
        'preference_order',
        'notes',
    ];

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<HousingUnit, $this>
     */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }
}
