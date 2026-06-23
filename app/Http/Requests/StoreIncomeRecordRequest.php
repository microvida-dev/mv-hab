<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesIncomeRecord;
use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeRecordRequest extends FormRequest
{
    use ValidatesIncomeRecord;

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
        return $this->incomeRules();
    }
}
