<?php

namespace App\Http\Requests\Submissions;

use Illuminate\Foundation\Http\FormRequest;

class ReportSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_reason' => ['required', 'string', 'in:spam,offensive,duplicate,irrelevant,other'],
            'report_note' => ['nullable', 'required_if:report_reason,other', 'string', 'max:1000'],
        ];
    }
}
