<?php

namespace App\Models;

use Database\Factories\InspectionChecklistTemplateItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionChecklistTemplateItem extends Model
{
    /** @use HasFactory<InspectionChecklistTemplateItemFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    /** @return BelongsTo<InspectionChecklistTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionChecklistTemplate::class, 'inspection_checklist_template_id');
    }
}
