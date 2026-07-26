<?php

namespace App\Http\Requests;

use App\Models\HousingVisit;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CompleteVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $visit = $this->route('housingVisit');
        $actor = $this->user();
        $ability = $this->routeIs(
            'backoffice.housing-visits.no-show',
        )
            ? 'markNoShowBackoffice'
            : 'completeBackoffice';

        return $actor instanceof User
            && $visit instanceof HousingVisit
            && $actor->can($ability, $visit);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'staff_notes' => ['required', 'string', 'max:2000'],
        ];
    }
}
