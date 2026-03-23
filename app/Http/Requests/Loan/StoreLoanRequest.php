<?php

declare(strict_types=1);

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth middleware handles authorization
    }

    public function rules(): array
    {
        return [
            'pdf_file' => 'nullable|file|mimes:pdf|max:2048',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ];
    }
}
