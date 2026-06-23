<?php

namespace App\Http\Requests\Simulator;

use App\Http\Requests\Simulator\Concerns\ValidatesSimulationInput;
use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateSimulationRequest extends FormRequest
{
    use ValidatesSimulationInput;

    public function authorize(): bool
    {
        return $this->user()?->hasRole('candidate') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->simulationRules();
    }
}
