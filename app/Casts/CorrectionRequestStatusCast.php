<?php

namespace App\Casts;

use App\Enums\CorrectionRequestStatus;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

/** @implements CastsAttributes<CorrectionRequestStatus, CorrectionRequestStatus|string> */
class CorrectionRequestStatusCast implements CastsAttributes
{
    public function get(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): CorrectionRequestStatus {
        $raw = (string) $value;
        $canonical = CorrectionRequestStatus::tryFrom($raw);

        if ($canonical instanceof CorrectionRequestStatus) {
            return $canonical;
        }

        return match ($raw) {
            'issued' => CorrectionRequestStatus::Notified,
            'partially_responded' => CorrectionRequestStatus::PartiallyCompleted,
            'responded', 'under_review' => CorrectionRequestStatus::Submitted,
            'overdue' => CorrectionRequestStatus::Expired,
            'accepted', 'closed' => CorrectionRequestStatus::Resolved,
            'cancelled' => CorrectionRequestStatus::Cancelled,
            default => throw new UnexpectedValueException(
                sprintf(
                    'O estado legacy "%s" do pedido de aperfeiçoamento exige regularização explícita.',
                    $raw,
                ),
            ),
        };
    }

    public function set(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): string {
        if ($value instanceof CorrectionRequestStatus) {
            return $value->value;
        }

        $status = CorrectionRequestStatus::tryFrom((string) $value);

        if (! $status instanceof CorrectionRequestStatus) {
            throw new UnexpectedValueException(
                'Não é permitido persistir um estado não canónico de aperfeiçoamento.',
            );
        }

        return $status->value;
    }
}
