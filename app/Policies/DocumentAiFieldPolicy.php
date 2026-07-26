<?php

namespace App\Policies;

use App\Models\DocumentAiField;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class DocumentAiFieldPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function view(User $user, DocumentAiField $field): bool
    {
        $analysis = $field->analysis;

        return $analysis !== null
            && app(DocumentAiAnalysisPolicy::class)->viewExtractedFields($user, $analysis);
    }

    public function viewSensitive(User $user, DocumentAiField $field): bool
    {
        $analysis = $field->analysis;

        return $analysis !== null
            && app(DocumentAiAnalysisPolicy::class)->viewSensitiveExtractedFields($user, $analysis);
    }

    public function viewHealth(User $user, DocumentAiField $field): bool
    {
        $analysis = $field->analysis;

        return $analysis !== null
            && app(DocumentAiAnalysisPolicy::class)->viewHealthExtractedFields($user, $analysis);
    }

    public function markForReview(User $user, DocumentAiField $field): bool
    {
        $analysis = $field->analysis;

        return $analysis !== null
            && app(DocumentAiAnalysisPolicy::class)->markFieldForReview($user, $analysis);
    }

    public function reviewBackoffice(User $user, DocumentAiField $field): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'review_ai')
            && $this->municipalScope->ownsDocumentAiField($user, $field);
    }
}
