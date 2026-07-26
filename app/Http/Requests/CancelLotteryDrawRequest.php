<?php

namespace App\Http\Requests;

use App\Models\LotteryDraw;
use Illuminate\Foundation\Http\FormRequest;

class CancelLotteryDrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        $draw = $this->route('lotteryDraw');

        return $draw instanceof LotteryDraw
            && ($this->user()?->can('cancelBackoffice', $draw) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:3000'],
        ];
    }
}
