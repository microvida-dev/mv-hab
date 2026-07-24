<?php

namespace App\Http\Requests;

use App\Models\ProvisionalList;
use Illuminate\Foundation\Http\FormRequest;

class ApproveProvisionalListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $list = $this->route('provisionalList');

        return $list instanceof ProvisionalList
            && ($this->user()?->can('approveBackoffice', $list) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
