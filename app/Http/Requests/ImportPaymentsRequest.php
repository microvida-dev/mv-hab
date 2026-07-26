<?php

namespace App\Http\Requests;

use App\Models\PaymentImportBatch;
use Illuminate\Foundation\Http\FormRequest;

class ImportPaymentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createBackoffice', PaymentImportBatch::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
