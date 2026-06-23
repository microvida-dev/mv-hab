<?php

namespace App\Models;

use App\Enums\DocumentDossierStatus;
use Database\Factories\DocumentDossierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentDossier extends Model
{
    /** @use HasFactory<DocumentDossierFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentDossierStatus::class,
            'standardization_payload' => 'array',
            'missing_documents_count' => 'integer',
            'rejected_documents_count' => 'integer',
            'expired_documents_count' => 'integer',
            'validated_documents_count' => 'integer',
            'standardized_at' => 'datetime',
            'exported_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'dossier_number';
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<DocumentDossierItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(DocumentDossierItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
