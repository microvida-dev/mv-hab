<?php

namespace App\Http\Requests;

use App\Models\Application;
use App\Models\HousingPreference;
use App\Services\Allocation\AllocationRuleSetResolver;
use App\Services\Allocation\HousingPreferenceService;
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
        $compatibleCount = $enabled
            ? app(HousingPreferenceService::class)
                ->optionsFor($application)
                ->count()
            : 0;
        $required = $enabled && $this->requiresMinimum();
        $preferences = $this->input('preferences');
        $hasPreferences = is_array($preferences)
            && $preferences !== [];
        $completeOrderRequired = $required || $hasPreferences;

        return [
            'preferences' => [
                $required ? 'required' : 'present',
                'array',
                $completeOrderRequired
                    ? 'size:'.$compatibleCount
                    : 'max:0',
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
            'preferences.required' => 'Ordene todos os fogos compatíveis antes de continuar.',
            'preferences.size' => 'A ordem deve incluir todos os fogos compatíveis, sem omissões.',
            'preferences.max' => 'A ordem contém mais fogos do que os atualmente compatíveis.',
            'preferences.*.contest_housing_unit_id.distinct' => 'O mesmo fogo não pode ocupar duas posições.',
            'preferences.*.preference_order.distinct' => 'Cada posição da ordem só pode ser usada uma vez.',
        ];
    }

    protected function requiresMinimum(): bool
    {
        return false;
    }
}
