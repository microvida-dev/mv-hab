<?php

namespace App\Http\Requests;

use App\Enums\LotteryDrawType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLotteryDrawRequest extends FormRequest
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
            'draw_type' => ['nullable', Rule::in(LotteryDrawType::values())],
            'scheduled_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'public_notice_text' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
