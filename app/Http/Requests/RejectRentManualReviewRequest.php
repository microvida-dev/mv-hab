<?php

namespace App\Http\Requests;

use App\Models\RentManualReview;
use Illuminate\Foundation\Http\FormRequest;

class RejectRentManualReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('rentManualReview');

        return $review instanceof RentManualReview
            && $this->user()?->can('rejectBackoffice', $review) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:3000']];
    }
}
