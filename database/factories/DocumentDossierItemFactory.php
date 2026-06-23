<?php

namespace Database\Factories;

use App\Enums\DocumentDossierItemStatus;
use App\Models\DocumentDossier;
use App\Models\DocumentDossierItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentDossierItem> */
class DocumentDossierItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_dossier_id' => DocumentDossier::factory(),
            'category' => 'identification',
            'label' => 'Documento fictício',
            'status' => DocumentDossierItemStatus::Missing,
            'is_required' => true,
            'sort_order' => 1,
        ];
    }
}
