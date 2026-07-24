<?php

namespace App\Http\Requests;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        $complaint = $this->route('complaint');

        return $complaint instanceof Complaint
            && ($this->user()?->can('assignBackoffice', $complaint) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => [
                'required',
                Rule::exists('users', 'id')->where(
                    'municipality_id',
                    $this->user()?->municipality_id,
                ),
            ],
        ];
    }
}
