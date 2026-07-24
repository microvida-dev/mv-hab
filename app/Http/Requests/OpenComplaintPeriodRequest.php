<?php

namespace App\Http\Requests;

use App\Models\ProvisionalList;
use Illuminate\Foundation\Http\FormRequest;

class OpenComplaintPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $list = $this->route('provisionalList');

        return $list instanceof ProvisionalList
            && ($this->user()?->can('openComplaintPeriodBackoffice', $list) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'complaint_period_starts_at' => ['nullable', 'date'],
            'complaint_period_ends_at' => ['nullable', 'date', 'after_or_equal:complaint_period_starts_at'],
        ];
    }
}
