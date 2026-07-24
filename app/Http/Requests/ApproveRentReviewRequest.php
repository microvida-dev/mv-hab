<?php

namespace App\Http\Requests;

use App\Models\RentReview;
use Illuminate\Foundation\Http\FormRequest;

class ApproveRentReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('rentReview');

        return $review instanceof RentReview
            && $this->user()?->can('approveBackoffice', $review) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'approved_rent' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
