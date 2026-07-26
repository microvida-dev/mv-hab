<?php

namespace App\Http\Requests;

use App\Models\ComplaintDecision;
use Illuminate\Foundation\Http\FormRequest;

class ApproveComplaintDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $decision = $this->route('complaintDecision');

        return $decision instanceof ComplaintDecision
            && ($this->user()?->can('approveBackoffice', $decision) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
