<?php

namespace App\Models;

use App\Enums\InspectionReportStatus;
use Database\Factories\PropertyInspectionReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyInspectionReport extends Model
{
    /** @use HasFactory<PropertyInspectionReportFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'report_number', 'status', 'storage_disk', 'storage_path', 'checksum'];

    protected function casts(): array
    {
        return [
            'status' => InspectionReportStatus::class,
            'generated_at' => 'datetime',
            'validated_at' => 'datetime',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PropertyInspection, $this>
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(PropertyInspection::class, 'property_inspection_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
