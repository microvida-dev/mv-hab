<?php

namespace App\Services\Reporting\Temporal;

use App\Services\Support\CanonicalJsonHasher;
use JsonException;

final class SpreadsheetCellSanitizer
{
    public function __construct(
        private readonly CanonicalJsonHasher $hasher,
    ) {}

    /** @throws JsonException */
    public function value(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $text = is_array($value) || is_object($value)
            ? $this->hasher->encode($value)
            : (string) $value;

        return preg_match('/^[=+\-@]/u', $text) === 1 ? "'".$text : $text;
    }
}
