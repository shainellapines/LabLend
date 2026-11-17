<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * FR-H3: date range (and export format) for the reports page.
 */
class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'format' => ['nullable', 'in:pdf,csv'],
        ];
    }

    /** [from, to] as whole days. Defaults to the last 30 days. */
    public function dateRange(): array
    {
        return [
            $this->filled('from') ? Carbon::parse($this->input('from'))->startOfDay() : today()->subDays(29),
            $this->filled('to') ? Carbon::parse($this->input('to'))->endOfDay() : today()->endOfDay(),
        ];
    }
}
