<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LockScoringRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('lock', $this->route('scoringRun')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
