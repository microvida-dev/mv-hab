<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunLotteryRequest extends FormRequest
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
            'allocation_run_id' => ['required', 'exists:allocation_runs,id'],
            'seed' => ['nullable', 'string', 'max:255'],
            'seed_source' => ['nullable', 'string', 'max:255'],
            'algorithm' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
