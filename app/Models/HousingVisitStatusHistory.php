<?php

namespace App\Models;

use App\Enums\VisitStatus;
use Database\Factories\HousingVisitStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousingVisitStatusHistory extends Model
{
    /** @use HasFactory<HousingVisitStatusHistoryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => VisitStatus::class,
            'to_status' => VisitStatus::class,
            'changed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<HousingVisit, $this>
     */
    public function housingVisit(): BelongsTo
    {
        return $this->belongsTo(HousingVisit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
