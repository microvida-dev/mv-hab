<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesHouseholdMember;
use Illuminate\Foundation\Http\FormRequest;

class StoreHouseholdMemberRequest extends FormRequest
{
    use ValidatesHouseholdMember;

    public function authorize(): bool
    {
        $household = $this->currentHousehold();

        return $household !== null && $this->user()?->can('update', $household) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->memberRules();
    }
}
