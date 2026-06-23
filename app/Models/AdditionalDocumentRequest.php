<?php

namespace App\Models;

use App\Enums\ProcessActionStatus;
use Database\Factories\AdditionalDocumentRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property ProcessActionStatus $status
 * @property Carbon|null $due_at
 */
class AdditionalDocumentRequest extends Model
{
    /** @use HasFactory<AdditionalDocumentRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['status', 'title', 'description', 'due_at', 'internal_notes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProcessActionStatus::class,
            'due_at' => 'datetime',
            'issued_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'request_number';
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

    /** @return BelongsTo<DocumentType, $this> */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /** @return BelongsTo<RequiredDocument, $this> */
    public function requiredDocument(): BelongsTo
    {
        return $this->belongsTo(RequiredDocument::class);
    }

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** @return HasMany<AdditionalDocumentSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(AdditionalDocumentSubmission::class);
    }
}
