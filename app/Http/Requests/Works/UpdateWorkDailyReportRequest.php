<?php

namespace App\Http\Requests\Works;

class UpdateWorkDailyReportRequest extends StoreWorkDailyReportRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }
}

