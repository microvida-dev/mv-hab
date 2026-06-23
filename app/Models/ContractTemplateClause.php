<?php

namespace App\Models;

use Database\Factories\ContractTemplateClauseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTemplateClause extends Model
{
    /** @use HasFactory<ContractTemplateClauseFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return BelongsTo<ContractTemplate, $this>
     */
    public function contractTemplate(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class);
    }

    /**
     * @return BelongsTo<ContractClause, $this>
     */
    public function contractClause(): BelongsTo
    {
        return $this->belongsTo(ContractClause::class);
    }
}
