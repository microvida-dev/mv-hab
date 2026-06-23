<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CallNextReserveCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'replacement_for_allocation_id' => ['required', 'exists:allocations,id'],
        ];
    }
}
