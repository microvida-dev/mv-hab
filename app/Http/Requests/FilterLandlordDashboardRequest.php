<?php

namespace App\Http\Requests;

use App\Models\LandlordDashboardSnapshot;
use Illuminate\Foundation\Http\FormRequest;

class FilterLandlordDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'viewAnyBackoffice',
            LandlordDashboardSnapshot::class,
        ) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
