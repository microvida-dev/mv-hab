<?php

namespace App\Http\Requests;

use App\Models\ListAutomationRun;
use Illuminate\Foundation\Http\FormRequest;

class ApproveListAutomationRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        $run = $this->route('listAutomationRun');

        return $run instanceof ListAutomationRun
            && ($this->user()?->can('approveBackoffice', $run) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['confirm_reviewed' => ['nullable', 'boolean']];
    }
}
