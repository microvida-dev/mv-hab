<?php

namespace Database\Factories;

use App\Models\PermissionReview;
use App\Models\PermissionReviewItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PermissionReviewItem> */
class PermissionReviewItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'permission_review_id' => PermissionReview::factory(),
            'module' => 'permissions',
            'risk_level' => 'medium',
            'finding' => 'Finding demo.',
            'recommendation' => 'Recomendação demo.',
        ];
    }
}
