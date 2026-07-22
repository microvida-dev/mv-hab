<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CancelAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'rejectBackoffice',
            $this->route('allocationRun'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['cancellation_reason' => ['required', 'string', 'min:5', 'max:3000']];
    }
}
