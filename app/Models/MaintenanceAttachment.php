<?php

namespace App\Models;

use App\Enums\MaintenanceAttachmentType;
use Database\Factories\MaintenanceAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceAttachment extends Model
{
    /** @use HasFactory<MaintenanceAttachmentFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attachment_type' => MaintenanceAttachmentType::class,
            'visible_to_tenant' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<MaintenanceRequest, $this>
     */
    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    /**
     * @return BelongsTo<MaintenanceIntervention, $this>
     */
    public function intervention(): BelongsTo
    {
        return $this->belongsTo(MaintenanceIntervention::class, 'maintenance_intervention_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
