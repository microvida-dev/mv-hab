<?php

namespace App\Http\Requests;

use App\Models\LotteryDraw;
use Illuminate\Foundation\Http\FormRequest;

class RunLotteryDrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        $draw = $this->route('lotteryDraw');

        return $draw instanceof LotteryDraw
            && ($this->user()?->can('runBackoffice', $draw) ?? false);
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
