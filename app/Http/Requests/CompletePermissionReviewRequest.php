<?php

namespace App\Http\Requests;

use App\Models\PermissionReview;
use Illuminate\Foundation\Http\FormRequest;

class CompletePermissionReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('permissionReview');

        return $review instanceof PermissionReview
            && ($this->user()?->can('complete', $review) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['summary' => ['nullable', 'string', 'max:5000']];
    }
}
