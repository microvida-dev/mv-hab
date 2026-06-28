<?php

namespace App\Services\Search\Sources\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

trait BuildsSearchResults
{
    protected function containsTerm(string $haystack, string $term): bool
    {
        if ($term === '') {
            return true;
        }

        return Str::contains(Str::lower($haystack), Str::lower($term));
    }

    protected function enumLabel(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        return is_scalar($value) ? (string) $value : '';
    }

    protected function relatedAttribute(Model $model, string $relation, string $attribute): ?string
    {
        $related = $model->getRelationValue($relation);

        if (! $related instanceof Model) {
            return null;
        }

        $value = $related->getAttribute($attribute);

        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }

    protected function relatedDisplayTitle(Model $model, string $relation): ?string
    {
        $related = $model->getRelationValue($relation);

        if (! $related instanceof Model || ! is_callable([$related, 'displayTitle'])) {
            return null;
        }

        $value = call_user_func([$related, 'displayTitle']);

        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }
}
