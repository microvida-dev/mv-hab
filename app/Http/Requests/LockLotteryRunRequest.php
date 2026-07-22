<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class LockLotteryRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'approveBackoffice',
            $this->route('lotteryRun'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
