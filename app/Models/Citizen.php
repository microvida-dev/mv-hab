<?php

namespace App\Models;

use Database\Factories\CitizenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Citizen extends Model
{
    /** @use HasFactory<CitizenFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'document_number',
        'birth_date',
        'phone',
        'email',
        'address',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /**
     * @return HasMany<Household, $this>
     */
    public function households(): HasMany
    {
        return $this->hasMany(Household::class);
    }

    /**
     * @return HasMany<HousingApplication, $this>
     */
    public function housingApplications(): HasMany
    {
        return $this->hasMany(HousingApplication::class);
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * @return HasMany<MaintenanceRequest, $this>
     */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
