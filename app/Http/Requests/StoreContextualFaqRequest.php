<?php

namespace App\Http\Requests;

use App\Models\ContextualFaq;
use Illuminate\Foundation\Http\FormRequest;

class StoreContextualFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ContextualFaq::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contextual_faq_category_id' => ['nullable', 'exists:contextual_faq_categories,id'],
            'contest_id' => ['nullable', 'exists:contests,id'],
            'context_key' => ['required', 'string', 'max:100'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:10000'],
            'keywords' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
