<?php

namespace Tests\Unit\Administrative;

use App\Services\Support\CanonicalJsonHasher;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CanonicalJsonHasherTest extends TestCase
{
    #[Test]
    public function associative_key_order_does_not_change_hash(): void
    {
        $hasher = app(CanonicalJsonHasher::class);

        $this->assertSame(
            $hasher->hash([
                'contest' => 10,
                'item' => ['status' => 'ready', 'id' => 2],
            ]),
            $hasher->hash([
                'item' => ['id' => 2, 'status' => 'ready'],
                'contest' => 10,
            ]),
        );
    }

    #[Test]
    public function list_order_remains_part_of_canonical_hash(): void
    {
        $hasher = app(CanonicalJsonHasher::class);

        $this->assertNotSame(
            $hasher->hash(['items' => [1, 2, 3]]),
            $hasher->hash(['items' => [3, 2, 1]]),
        );
    }
}
