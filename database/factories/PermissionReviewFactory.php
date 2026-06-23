<?php

namespace Database\Factories;

use App\Models\PermissionReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PermissionReview> */
class PermissionReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'review_number' => 'PERM-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'status' => 'in_progress',
            'scope' => 'all',
            'started_by' => User::factory(),
            'started_at' => now(),
            'summary' => 'Revisão demo.',
        ];
    }
}
