<?php

namespace App\Http\Requests;

use App\Models\LotteryDraw;
use Illuminate\Foundation\Http\FormRequest;

class GenerateDrawConvocationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $draw = $this->route('lotteryDraw');

        return $draw instanceof LotteryDraw
            && ($this->user()?->can('generateConvocationsBackoffice', $draw) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scheduled_for' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
