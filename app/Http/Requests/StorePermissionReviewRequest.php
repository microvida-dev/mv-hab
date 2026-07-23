<?php

namespace App\Http\Requests;

use App\Models\PermissionReview;
use Illuminate\Foundation\Http\FormRequest;

class StorePermissionReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PermissionReview::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['scope' => ['nullable', 'string', 'max:150']];
    }
}
