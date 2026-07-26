<?php

namespace App\Http\Requests;

use App\Models\HousingVisit;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RejectVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $visit = $this->route('housingVisit');
        $actor = $this->user();

        return $actor instanceof User
            && $visit instanceof HousingVisit
            && $actor->can('rejectBackoffice', $visit);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
