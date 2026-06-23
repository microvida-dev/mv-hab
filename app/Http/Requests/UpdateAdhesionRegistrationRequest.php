<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAdhesionRegistration;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdhesionRegistrationRequest extends FormRequest
{
    use ValidatesAdhesionRegistration;

    public function authorize(): bool
    {
        $registration = $this->user()?->adhesionRegistration;

        return $registration !== null && $this->user()->can('update', $registration);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->draftRules();
    }
}
