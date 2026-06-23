<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAdhesionRegistration;
use App\Models\AdhesionRegistration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreAdhesionRegistrationRequest extends FormRequest
{
    use ValidatesAdhesionRegistration;

    public function authorize(): bool
    {
        return Gate::allows('create', AdhesionRegistration::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->draftRules();
    }
}
