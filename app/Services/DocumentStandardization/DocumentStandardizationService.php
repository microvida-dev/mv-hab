<?php

namespace App\Services\DocumentStandardization;

use App\Enums\DocumentDossierItemStatus;
use App\Enums\DocumentStatus;
use App\Models\DocumentSubmission;
use App\Models\RequiredDocument;

class DocumentStandardizationService
{
    public function category(?string $category): string
    {
        return match ($category) {
            'identification' => 'Identificação',
            'residence' => 'Residência',
            'household' => 'Agregado familiar',
            'income' => 'Rendimentos',
            'housing' => 'Situação habitacional',
            'disability' => 'Deficiência/incapacidade',
            'declarations' => 'Declarações',
            default => 'Outros',
        };
    }

    public function label(RequiredDocument|DocumentSubmission $source): string
    {
        if ($source instanceof RequiredDocument) {
            return $source->documentType->name ?? 'Documento obrigatório';
        }

        return $source->title ?: ($source->documentType->name ?? 'Documento submetido');
    }

    public function itemStatus(?DocumentSubmission $submission): DocumentDossierItemStatus
    {
        if ($submission === null) {
            return DocumentDossierItemStatus::Missing;
        }

        return match ($submission->status) {
            DocumentStatus::Validated => DocumentDossierItemStatus::Validated,
            DocumentStatus::Rejected => DocumentDossierItemStatus::Rejected,
            DocumentStatus::Expired => DocumentDossierItemStatus::Expired,
            DocumentStatus::UnderReview => DocumentDossierItemStatus::UnderReview,
            default => DocumentDossierItemStatus::Submitted,
        };
    }
}
