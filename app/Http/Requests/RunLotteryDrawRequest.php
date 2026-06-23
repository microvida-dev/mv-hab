<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunLotteryDrawRequest extends FormRequest
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
            'seed' => ['nullable', 'string', 'max:255'],
        ];
    }
}
