<?php

namespace App\Http\Requests;

use App\Models\Application;
use App\Models\HousingPreference;
use App\Services\Allocation\AllocationRuleSetResolver;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHousingPreferenceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('preferences')) {
            $this->merge(['preferences' => []]);
        }
    }

    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof Application
            && ($this->user()?->can('update', [HousingPreference::class, $application]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $application = $this->route('application');
        $ruleSet = $application instanceof Application
            ? app(AllocationRuleSetResolver::class)->forApplication($application)
            : null;
        $enabled = $ruleSet?->allow_preferences === true;
        $minimum = $enabled
            && $this->requiresMinimum()
            && $ruleSet->preferences_required_before_submission
                ? max(1, (int) $ruleSet->minimum_preferences)
                : 0;
        $maximum = $enabled
            ? max($minimum, $ruleSet->maximum_preferences)
            : 0;

        return [
            'preferences' => [
                $this->requiresMinimum() ? 'required' : 'present',
                'array',
                'min:'.$minimum,
                'max:'.$maximum,
            ],
            'preferences.*.contest_housing_unit_id' => [
                'required',
                'integer',
                'distinct',
            ],
            'preferences.*.preference_order' => [
                'required',
                'integer',
                'distinct',
                'min:1',
                'max:'.max(1, $maximum),
            ],
            'preferences.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preferences.required' => 'Selecione pelo menos uma habitação compatível.',
            'preferences.min' => 'Selecione o número mínimo de habitações indicado.',
            'preferences.max' => 'Não pode selecionar mais habitações do que o limite indicado.',
            'preferences.*.contest_housing_unit_id.distinct' => 'A mesma habitação não pode ocupar duas posições.',
            'preferences.*.preference_order.distinct' => 'Cada posição da ordem só pode ser usada uma vez.',
        ];
    }

    protected function requiresMinimum(): bool
    {
        return false;
    }
}
