<?php

namespace App\Http\Requests;

use App\Models\AnnualDocumentUpdateRequest;
use App\Models\IncomeChangeDeclaration;
use Illuminate\Foundation\Http\FormRequest;

class ReviewIncomeChangeDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $annualRequest = $this->route('annualDocumentUpdateRequest');
        if ($annualRequest instanceof AnnualDocumentUpdateRequest) {
            return $this->user()?->can('approveBackoffice', $annualRequest) === true;
        }

        $declaration = $this->route('incomeChangeDeclaration');

        return $declaration instanceof IncomeChangeDeclaration
            && $this->user()?->can('approveBackoffice', $declaration) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:3000'],
            'reason' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
