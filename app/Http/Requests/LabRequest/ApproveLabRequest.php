<?php

declare(strict_types=1);

namespace App\Http\Requests\LabRequest;

use Illuminate\Foundation\Http\FormRequest;

class ApproveLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lab_id' => 'required|exists:labs,id',
            'table_id' => 'required|exists:tables,id',
        ];
    }
}
