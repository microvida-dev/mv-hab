<?php

namespace App\Http\Requests;

use App\Models\ProvisionalList;
use Illuminate\Foundation\Http\FormRequest;

class CloseComplaintPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $list = $this->route('provisionalList');

        return $list instanceof ProvisionalList
            && ($this->user()?->can('closeComplaintPeriodBackoffice', $list) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
