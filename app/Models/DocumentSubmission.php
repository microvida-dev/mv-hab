<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Database\Factories\DocumentSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $current_version_id
 * @property DocumentStatus|null $status
 * @property string|null $storage_disk
 * @property string|null $storage_path
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property string|null $checksum
 * @property-read User|null $user
 * @property-read DocumentVersion|null $currentVersion
 */
class DocumentSubmission extends Model
{
    /** @use HasFactory<DocumentSubmissionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'issue_date',
        'expiry_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'file_size' => 'integer',
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'validated_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<AdhesionRegistration, $this> */
    public function adhesionRegistration(): BelongsTo
    {
        return $this->belongsTo(AdhesionRegistration::class);
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<HouseholdMember, $this> */
    public function householdMember(): BelongsTo
    {
        return $this->belongsTo(HouseholdMember::class);
    }

    /** @return BelongsTo<IncomeRecord, $this> */
    public function incomeRecord(): BelongsTo
    {
        return $this->belongsTo(IncomeRecord::class);
    }

    /** @return BelongsTo<CurrentHousingSituation, $this> */
    public function currentHousingSituation(): BelongsTo
    {
        return $this->belongsTo(CurrentHousingSituation::class);
    }

    /** @return BelongsTo<Contract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsToMany<Application, $this> */
    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'application_documents')
            ->withPivot(['document_type_id', 'is_required', 'status_at_submission'])
            ->withTimestamps();
    }

    /** @return BelongsTo<DocumentVersion, $this> */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /** @return HasMany<DocumentVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    /** @return HasMany<DocumentReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(DocumentReview::class);
    }

    /** @return HasMany<DocumentAccessLog, $this> */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(DocumentAccessLog::class);
    }

    /** @return HasMany<DocumentAiAnalysis, $this> */
    public function documentAiAnalyses(): HasMany
    {
        return $this->hasMany(DocumentAiAnalysis::class);
    }

    /** @return HasOne<DocumentAiAnalysis, $this> */
    public function latestDocumentAiAnalysis(): HasOne
    {
        return $this->hasOne(DocumentAiAnalysis::class)->latestOfMany();
    }

    public function isReplaceable(): bool
    {
        $status = $this->status instanceof DocumentStatus
            ? $this->status
            : DocumentStatus::tryFrom((string) $this->status);

        return in_array($status, [
            DocumentStatus::Submitted,
            DocumentStatus::Rejected,
            DocumentStatus::Expired,
        ], true);
    }
}
