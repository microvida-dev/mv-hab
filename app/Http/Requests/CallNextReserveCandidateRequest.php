<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CallNextReserveCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'updateBackoffice',
            $this->route('reserveList'),
        );
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
