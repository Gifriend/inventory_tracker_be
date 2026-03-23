<?php

declare(strict_types=1);

namespace App\Http\Requests\LabRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pdf_file' => 'required|file|mimes:pdf|max:2048',
            'message' => 'nullable|string',
        ];
    }
}
