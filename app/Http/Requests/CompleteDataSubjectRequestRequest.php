<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CompleteDataSubjectRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->municipality_id !== null
            && $user->hasPermission('privacy.approve');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['summary' => ['required', 'string', 'min:5', 'max:5000']];
    }
}
