<?php

namespace Database\Factories;

use App\Enums\CorrectionIssueType;
use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequiredAction;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorrectionRequestItem>
 */
class CorrectionRequestItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'correction_request_id' => CorrectionRequest::factory(),
            'issue_type' => CorrectionIssueType::MissingData->value,
            'title' => 'Elemento em falta',
            'description' => 'Descrição fictícia do elemento a aperfeiçoar.',
            'required_action' => CorrectionRequiredAction::ProvideExplanation->value,
            'status' => CorrectionRequestItemStatus::Pending->value,
            'is_required' => true,
            'sort_order' => 1,
        ];
    }
}
