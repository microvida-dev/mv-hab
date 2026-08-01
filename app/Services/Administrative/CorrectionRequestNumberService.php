<?php

namespace App\Services\Administrative;

use Illuminate\Support\Facades\DB;

class CorrectionRequestNumberService
{
    public function next(int $municipalityId, ?int $year = null): string
    {
        $sequenceYear = $year ?? (int) now()->format('Y');

        DB::table('correction_request_sequences')->insertOrIgnore([
            'municipality_id' => $municipalityId,
            'year' => $sequenceYear,
            'next_value' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('correction_request_sequences')
            ->where('municipality_id', $municipalityId)
            ->where('year', $sequenceYear)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            throw new \RuntimeException(
                'Não foi possível reservar a sequência do pedido de aperfeiçoamento.',
            );
        }

        $value = (int) $sequence->next_value;

        DB::table('correction_request_sequences')
            ->where('id', $sequence->id)
            ->update([
                'next_value' => $value + 1,
                'updated_at' => now(),
            ]);

        return sprintf(
            'APR-%d-%d-%06d',
            $municipalityId,
            $sequenceYear,
            $value,
        );
    }
}
