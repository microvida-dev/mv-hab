<?php

namespace App\Http\Requests\Simulator;

use App\Http\Requests\Simulator\Concerns\ValidatesSimulationInput;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnonymousSimulationRequest extends FormRequest
{
    use ValidatesSimulationInput;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->simulationRules();
    }
}
