<?php

namespace Database\Factories;

use App\Enums\CorrectionResponseStatus;
use App\Models\CorrectionRequestItem;
use App\Models\CorrectionResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorrectionResponse>
 */
class CorrectionResponseFactory extends Factory
{
    public function definition(): array
    {
        $item = CorrectionRequestItem::factory()->create();
        $request = $item->correctionRequest()->firstOrFail();

        return [
            'correction_request_id' => $request->id,
            'correction_request_item_id' => $item->id,
            'application_id' => $request->application_id,
            'user_id' => $request->user_id,
            'response_text' => 'Resposta fictícia de aperfeiçoamento.',
            'status' => CorrectionResponseStatus::Submitted->value,
            'submitted_at' => now(),
        ];
    }
}
