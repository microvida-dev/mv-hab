<?php

namespace Database\Factories;

use App\Enums\ListPublicationChannel;
use App\Enums\ListPublicationStatus;
use App\Enums\ListPublicationType;
use App\Models\ListPublication;
use App\Models\ProvisionalList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ListPublication> */
class ListPublicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'publishable_type' => ProvisionalList::class,
            'publishable_id' => ProvisionalList::factory(),
            'publication_type' => ListPublicationType::ProvisionalList->value,
            'status' => ListPublicationStatus::Published->value,
            'channel' => ListPublicationChannel::CandidateArea->value,
            'title' => 'Publicação fictícia',
            'published_by' => User::factory(),
            'published_at' => now(),
            'visibility_starts_at' => now(),
            'anonymization_mode' => 'public_identifier_only',
        ];
    }
}
