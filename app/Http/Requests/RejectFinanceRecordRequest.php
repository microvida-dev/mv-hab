<?php

namespace App\Http\Requests;

use App\Models\AnnualDocumentUpdateRequest;
use App\Models\IncomeChangeDeclaration;
use App\Models\RentReview;
use Illuminate\Foundation\Http\FormRequest;

class RejectFinanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $annualRequest = $this->route('annualDocumentUpdateRequest');
        if ($annualRequest instanceof AnnualDocumentUpdateRequest) {
            return $this->user()?->can('rejectBackoffice', $annualRequest) === true;
        }

        $declaration = $this->route('incomeChangeDeclaration');
        if ($declaration instanceof IncomeChangeDeclaration) {
            return $this->user()?->can('rejectBackoffice', $declaration) === true;
        }

        $rentReview = $this->route('rentReview');

        return $rentReview instanceof RentReview
            && $this->user()?->can('rejectBackoffice', $rentReview) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
