<?php

namespace App\Http\Requests;

use App\Enums\TieBreakerDirection;
use App\Models\TieBreakerRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTieBreakerRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('tieBreakerRule')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rule = $this->route('tieBreakerRule');

        if (! $rule instanceof TieBreakerRule) {
            abort(404);
        }

        return [
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tie_breaker_rules', 'code')
                    ->where('scoring_rule_set_id', $rule->scoring_rule_set_id)
                    ->ignore($rule->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'target' => ['required', 'string', 'max:100'],
            'direction' => ['required', 'in:'.implode(',', TieBreakerDirection::values())],
            'priority_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
