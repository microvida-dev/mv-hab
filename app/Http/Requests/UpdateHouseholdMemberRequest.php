<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesHouseholdMember;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHouseholdMemberRequest extends FormRequest
{
    use ValidatesHouseholdMember;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('member')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->memberRules();
    }
}
