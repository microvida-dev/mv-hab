<?php

namespace App\Http\Requests;

use App\Models\EligibilityCheck;
use Illuminate\Foundation\Http\FormRequest;

class RunApplicationEligibilityCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('runFormal', [
            EligibilityCheck::class,
            $this->route('application'),
        ]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
