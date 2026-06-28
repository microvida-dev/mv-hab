<?php

namespace App\Services\Search\Contracts;

use App\Models\User;

interface SearchSource
{
    public function key(): string;

    public function label(): string;

    public function minimumCharacters(): int;

    /**
     * @return list<array<string, mixed>>
     */
    public function search(User $user, string $term, int $limit): array;
}
