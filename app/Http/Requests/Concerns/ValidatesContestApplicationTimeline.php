<?php

namespace App\Http\Requests\Concerns;

use App\Services\Contests\ContestApplicationTimelineService;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

trait ValidatesContestApplicationTimeline
{
    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $input = $this->input('deadlines', []);
            $deadlines = [];

            if (is_array($input)) {
                foreach ($input as $deadline) {
                    if (is_array($deadline)) {
                        $deadlines[] = $deadline;
                    }
                }
            }

            try {
                app(ContestApplicationTimelineService::class)->normalize(
                    $this->input('opens_at'),
                    $this->input('closes_at'),
                    $deadlines,
                );
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        }];
    }
}
