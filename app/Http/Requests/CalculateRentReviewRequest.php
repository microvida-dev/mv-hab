<?php

namespace App\Http\Requests;

use App\Models\RentReview;
use Illuminate\Foundation\Http\FormRequest;

class CalculateRentReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('rentReview');

        return $review instanceof RentReview
            && $this->user()?->can('calculateBackoffice', $review) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'proposed_rent' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
