<?php

namespace App\Http\Requests;

use App\Models\LotteryRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RunLotteryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'createBackoffice',
            LotteryRun::class,
        );
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
