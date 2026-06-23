<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesIncomeRecord;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIncomeRecordRequest extends FormRequest
{
    use ValidatesIncomeRecord;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('incomeRecord')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->incomeRules();
    }
}
