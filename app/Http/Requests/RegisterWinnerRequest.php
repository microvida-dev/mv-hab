<?php

namespace App\Http\Requests;

use App\Models\LotteryResult;
use Illuminate\Foundation\Http\FormRequest;

class RegisterWinnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $result = $this->route('lotteryResult');

        return $result instanceof LotteryResult
            && ($this->user()?->can('registerWinnerBackoffice', $result) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'validation_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
