<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ApproveAllocationReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'approveBackoffice',
            $this->route('allocationReport'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
