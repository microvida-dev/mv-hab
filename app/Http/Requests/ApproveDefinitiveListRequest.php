<?php

namespace App\Http\Requests;

use App\Models\DefinitiveList;
use Illuminate\Foundation\Http\FormRequest;

class ApproveDefinitiveListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $list = $this->route('definitiveList');

        return $list instanceof DefinitiveList
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
