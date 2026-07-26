<?php

namespace App\Http\Requests;

use App\Models\RentManualReview;
use Illuminate\Foundation\Http\FormRequest;

class ApproveRentManualReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('rentManualReview');

        return $review instanceof RentManualReview
            && $this->user()?->can('approveBackoffice', $review) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'approved_rent' => ['nullable', 'numeric', 'min:0'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
