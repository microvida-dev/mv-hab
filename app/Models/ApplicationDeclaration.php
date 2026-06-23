<?php

namespace App\Models;

use App\Enums\ApplicationDeclarationType;
use Database\Factories\ApplicationDeclarationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDeclaration extends Model
{
    /** @use HasFactory<ApplicationDeclarationFactory> */
    use HasFactory;

    protected $fillable = [
        'declaration_type',
        'accepted',
        'accepted_at',
        'text_version',
    ];

    protected function casts(): array
    {
        return [
            'declaration_type' => ApplicationDeclarationType::class,
            'accepted' => 'boolean',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
