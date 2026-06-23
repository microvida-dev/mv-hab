<?php

namespace App\Services\ProcedureMinutes;

use App\Models\ProcedureMinute;
use Illuminate\Support\Facades\Storage;

class ProcedureMinuteExportService
{
    public function export(ProcedureMinute $minute): string
    {
        $path = 'backoffice/procedure-minutes/'.$minute->minute_number.'.html';
        Storage::disk('local')->put(
            $path,
            '<!doctype html><html lang="pt"><meta charset="utf-8"><title>'.e($minute->title).'</title><body>'.$minute->content_snapshot.'</body></html>',
        );

        return $path;
    }
}
