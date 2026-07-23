<?php

namespace App\Http\Requests;

use App\Models\MfaDevice;
use Illuminate\Foundation\Http\FormRequest;

class DisableMfaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $device = $this->route('mfaDevice');

        return $device instanceof MfaDevice
            && ($this->user()?->can('update', $device) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['reason' => ['nullable', 'string', 'max:1000']];
    }
}
