<?php

namespace App\Http\Requests;

use App\Enums\LotteryDrawType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLotteryDrawRequest extends FormRequest
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
            'draw_type' => ['nullable', Rule::in(LotteryDrawType::values())],
            'seed' => ['nullable', 'string', 'max:255'],
            'seed_source' => ['nullable', 'string', 'max:255'],
            'algorithm' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'public_notice_text' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
