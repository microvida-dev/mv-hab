<?php

namespace App\Http\Requests;

use App\Models\ProcedureMinute;

class ApproveProcedureMinuteRequest extends PublishProcedureTemplateRequest
{
    public function authorize(): bool
    {
        $minute = $this->route('procedureMinute');

        return $minute instanceof ProcedureMinute
            && $this->user()?->can('approveBackoffice', $minute) === true;
    }
}
