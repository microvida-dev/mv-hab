<?php

namespace App\Http\Requests;

use App\Models\ContextualFaq;

class UpdateContextualFaqRequest extends StoreContextualFaqRequest
{
    public function authorize(): bool
    {
        $faq = $this->route('contextualFaq');

        return $faq instanceof ContextualFaq && ($this->user()?->can('update', $faq) ?? false);
    }
}
