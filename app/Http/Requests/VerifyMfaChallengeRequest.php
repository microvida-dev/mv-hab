<?php

namespace App\Http\Requests;

use App\Models\MfaDevice;
use Illuminate\Foundation\Http\FormRequest;

class VerifyMfaChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', MfaDevice::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20'],
        ];
    }
}
