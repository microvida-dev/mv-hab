<?php

namespace App\Services\Support;

use Illuminate\Database\Eloquent\Model;
use JsonException;

class CanonicalJsonHasher
{
    /**
     * @throws JsonException
     */
    public function encode(mixed $value): string
    {
        return json_encode(
            $this->canonicalize($value),
            JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_PRESERVE_ZERO_FRACTION,
        );
    }

    /**
     * @throws JsonException
     */
    public function hash(mixed $value): string
    {
        return hash('sha256', $this->encode($value));
    }

    /**
     * @throws JsonException
     */
    public function modelState(Model $model): string
    {
        return $this->hash($model->getRawOriginal());
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->canonicalize($item),
                $value,
            );
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
