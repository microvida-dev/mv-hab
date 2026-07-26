<?php

namespace App\Http\Requests;

use App\Models\DrawConvocation;
use Illuminate\Foundation\Http\FormRequest;

class SendDrawConvocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $convocation = $this->route('drawConvocation');

        return $convocation instanceof DrawConvocation
            && ($this->user()?->can('sendBackoffice', $convocation) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
