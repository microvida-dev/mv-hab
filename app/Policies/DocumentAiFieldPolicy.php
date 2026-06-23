<?php

namespace App\Policies;

use App\Models\DocumentAiField;
use App\Models\User;

class DocumentAiFieldPolicy
{
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
}
